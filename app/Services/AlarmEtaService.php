<?php

namespace App\Services;

use App\Models\Logger;
use App\Observers\BatteryMonitoringObserver;
use App\Observers\ChargerMonitoringObserver;
use App\Support\LinearTrend;
use App\Support\SensorRange;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Estimasi waktu sampai batas warning/alarm terlewati.
 *
 * - Log warning yang masih aktif: tren linear data monitoring terbaru sel/charger tersebut
 *   sampai batas alarm.
 * - Prediksi IR AI: interpolasi titik anchor → H+1 → H+7 → H+30 sampai batas IR, dan
 *   ekstrapolasi dari dua titik terakhir jika belum terlewati sampai horizon terjauh.
 *
 * Hasil berupa array dengan 'state':
 *   eta       : 'at' (Carbon) perkiraan waktu
 *   reached   : batas sudah terlewati
 *   beyond    : lebih jauh dari alarm.eta.max_days
 *   stable    : tren tidak menuju batas
 *   uncertain : data terlalu sedikit atau tren terlalu acak (R² rendah)
 */
class AlarmEtaService
{
    private ?array $checks = null;

    /**
     * ETA log warning yang masih aktif sampai level alarm. Null jika tidak relevan
     * (log sudah pulih, sensor fault, atau bukan log threshold).
     */
    public function forLog(Logger $log): ?array
    {
        $check = $this->checks()[$log->alarm_code] ?? null;

        if (! $check || ! $log->isOngoing()) {
            return null;
        }
        if ($log->status === 'alarm') {
            return ['state' => 'reached'];
        }

        $setting = $log->device_type === 'battery'
            ? $log->gardu?->batterySetting
            : $log->charger?->chargerSetting;
        $alarm = $setting?->{$check['alarm']};

        if (is_null($alarm)) {
            return ['state' => 'uncertain'];
        }

        $trend = $this->trend($log->device_type, (int) ($log->batt_id ?? $log->charger_id), $check['metric']);

        return $this->fromTrend($trend, (float) $alarm, $check['mode']);
    }

    /**
     * Tren linear satu metrik dari data monitoring terbaru perangkat. Data lebih dari 24 jam
     * dirata-rata per jam agar jumlah titik tetap kecil. Nilai sensor rusak tidak dipakai.
     */
    public function trend(string $device, int $id, string $metric): ?LinearTrend
    {
        [$table, $column] = $device === 'battery'
            ? ['battery_monitoring', 'battery_id']
            : ['charger_monitoring', 'charger_id'];

        $latest = DB::table($table)->where($column, $id)->max('measured_at');
        if (! $latest) {
            return null;
        }

        $hours = (int) (config("alarm.eta.window_hours.{$metric}") ?? config('alarm.eta.window_hours.default'));
        $since = Carbon::parse($latest)->subHours($hours)->format('Y-m-d H:i:s');
        $query = SensorRange::whereValid(DB::table($table), $device, [$metric => $metric])
            ->where($column, $id)
            ->where('measured_at', '>=', $since);

        $rows = $hours > 24
            ? $query->selectRaw($this->hourBucket().' as bucket, MIN(measured_at) as measured_at, AVG('.$metric.') as value')
                ->groupBy('bucket')
                ->orderBy('measured_at')
                ->get()
            : $query->orderBy('measured_at')->get(['measured_at', $metric.' as value']);

        if ($rows->count() < (int) config('alarm.eta.min_points')) {
            return null;
        }

        $timezone = config('app.timezone');

        return LinearTrend::fit($rows->map(fn ($row) => [
            (float) Carbon::parse($row->measured_at, $timezone)->getTimestamp(),
            (float) $row->value,
        ])->all());
    }

    public function fromTrend(?LinearTrend $trend, float $threshold, string $mode): array
    {
        if (! $trend || $trend->r2 < (float) config('alarm.eta.min_r2')) {
            return ['state' => 'uncertain', 'r2' => $trend?->r2];
        }

        $seconds = $trend->secondsUntil($threshold, $mode);

        if (is_null($seconds)) {
            return ['state' => 'stable', 'r2' => $trend->r2];
        }
        if ($seconds <= 0) {
            return ['state' => 'reached', 'r2' => $trend->r2];
        }
        if ($seconds > (int) config('alarm.eta.max_days') * 86400) {
            return ['state' => 'beyond', 'r2' => $trend->r2];
        }

        return [
            'state' => 'eta',
            'at' => Carbon::createFromTimestamp((int) round($trend->lastX + $seconds), config('app.timezone')),
            'r2' => $trend->r2,
        ];
    }

    /**
     * Tanggal prediksi IR pertama kali mencapai $threshold (mode max).
     *
     * @param  Collection  $forecasts  ir_forecast satu sel pada satu anchor
     */
    public function forecastCrossing(Carbon $anchor, float $rAnchor, Collection $forecasts, float $threshold): array
    {
        if ($rAnchor >= $threshold) {
            return ['state' => 'reached'];
        }

        $points = $forecasts->sortBy('horizon')
            ->map(fn ($f) => [(int) $f->horizon, (float) $f->r_pred])
            ->prepend([0, $rAnchor])
            ->values();
        $at = fn (float $days, bool $extrapolated) => [
            'state' => 'eta',
            'at' => $anchor->copy()->addDays((int) ceil($days)),
            'days' => (int) ceil($days),
            'extrapolated' => $extrapolated,
        ];

        for ($i = 1; $i < $points->count(); $i++) {
            [$d0, $v0] = $points[$i - 1];
            [$d1, $v1] = $points[$i];

            if ($v1 >= $threshold) {
                return $at($d0 + ($threshold - $v0) / ($v1 - $v0) * ($d1 - $d0), false);
            }
        }

        if ($points->count() < 2) {
            return ['state' => 'uncertain'];
        }

        // Belum terlewati sampai horizon terjauh: lanjutkan kemiringan dua titik terakhir
        [$d0, $v0] = $points[$points->count() - 2];
        [$d1, $v1] = $points->last();
        $slope = ($v1 - $v0) / ($d1 - $d0);

        if ($slope <= 0) {
            return ['state' => 'stable'];
        }

        $days = $d1 + ($threshold - $v1) / $slope;

        return $days > (int) config('alarm.eta.max_days') ? ['state' => 'beyond'] : $at($days, true);
    }

    private function checks(): array
    {
        return $this->checks ??= collect(BatteryMonitoringObserver::CHECKS)
            ->merge(ChargerMonitoringObserver::CHECKS)
            ->keyBy('code')
            ->all();
    }

    private function hourBucket(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d %H', measured_at)",
            'pgsql' => "to_char(measured_at, 'YYYY-MM-DD HH24')",
            'sqlsrv' => "FORMAT(measured_at, 'yyyy-MM-dd HH')",
            default => "DATE_FORMAT(measured_at, '%Y-%m-%d %H')",
        };
    }
}
