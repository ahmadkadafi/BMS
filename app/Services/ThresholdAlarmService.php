<?php

namespace App\Services;

use App\Models\Logger;
use App\Support\SensorRange;
use Illuminate\Support\Collection;

/**
 * Siklus hidup log warning/alarm dari data monitoring terhadap parameter setting.
 *
 * - Log dibuat jika N sampel terakhir (debounce) semuanya melanggar batas. Waktu kejadian
 *   adalah sampel tertua di jendela tersebut.
 * - Log naik ke alarm jika N sampel terakhir semuanya di level alarm. Level tidak turun lagi
 *   sampai log pulih, sehingga eskalasi tetap tercatat.
 * - Log pulih (cleared_at) jika N sampel terakhir normal dengan margin hysteresis. Gangguan
 *   berikutnya membuat log baru. Confirm/close operator tidak memengaruhi pemulihan.
 * - Nilai di luar rentang fisik (alarm.valid_ranges) dicatat sebagai sensor_fault dengan
 *   debounce yang sama. Sampel rusak tidak dihitung sebagai pelanggaran maupun normal.
 */
class ThresholdAlarmService
{
    /**
     * @param  array  $device  device_type, id_column (batt_id/charger_id), id, daop_id, resor_id, gardu_id
     * @param  array  $checks  code, metric, warn, alarm, label, unit, mode (min/max)
     * @param  Collection  $window  sampel terbaru lebih dulu, termasuk sampel saat ini
     */
    public function evaluate(array $device, array $checks, ?object $setting, Collection $window): void
    {
        if ($window->isEmpty()) {
            return;
        }

        $required = (int) config('alarm.debounce_samples');
        $window = $window->take($required)->values();
        $full = $window->count() >= $required;
        $metrics = array_keys(config("alarm.valid_ranges.{$device['device_type']}", []));
        $faultCode = fn (string $metric) => "{$device['device_type']}_{$metric}_fault";

        $ongoing = Logger::query()
            ->where('device_type', $device['device_type'])
            ->where($device['id_column'], $device['id'])
            ->whereIn('alarm_code', array_merge(array_column($checks, 'code'), array_map($faultCode, $metrics)))
            ->whereNull('cleared_at')
            ->orderBy('id')
            ->get()
            ->keyBy('alarm_code');

        // Flag rusak per metrik, urutan sama dengan $window
        $faults = collect($metrics)->mapWithKeys(fn (string $metric) => [
            $metric => $window->map(fn ($sample) => SensorRange::isFault($device['device_type'], $metric, $sample->{$metric})),
        ]);

        foreach ($faults as $metric => $flags) {
            $this->evaluateFault($device, $metric, $faultCode($metric), $window, $flags, $full, $ongoing[$faultCode($metric)] ?? null);
        }

        // Tanpa parameter setting hanya sensor fault yang dicek
        foreach ($setting ? $checks : [] as $check) {
            $warn = is_null($setting->{$check['warn']}) ? null : (float) $setting->{$check['warn']};
            $alarm = is_null($setting->{$check['alarm']}) ? null : (float) $setting->{$check['alarm']};
            $flags = $faults[$check['metric']] ?? collect();

            // Sampel saat ini rusak: tidak ada informasi baru untuk batas ini
            if ((is_null($warn) && is_null($alarm)) || $flags->first()) {
                continue;
            }

            // Jendela bersih = penuh dan tanpa sampel rusak; syarat buat log, eskalasi, dan pulih
            $clean = $full && ! $flags->contains(true);
            $values = $window->map(fn ($sample) => (float) $sample->{$check['metric']});
            $levels = $values->map(fn ($value) => $this->level($value, $warn, $alarm, $check['mode']));
            $value = $values->first();
            $level = $levels->first();
            $allAlarm = $clean && $levels->every(fn ($l) => $l === 'alarm');
            $log = $ongoing[$check['code']] ?? null;

            if ($log) {
                $this->continueLog($log, $check, $window, $values, $level, $allAlarm, $clean, $warn, $alarm);

                continue;
            }

            if (! $clean || $levels->contains(null)) {
                continue;
            }

            $logLevel = $allAlarm ? 'alarm' : 'warning';
            $occurredAt = $window->last()->measured_at;

            $this->createLog($device, $check['code'], $occurredAt, [
                'message' => $this->message($check, $value, $this->messageLevel($logLevel, $level), $warn, $alarm),
                'status' => $logLevel,
                'alarm_type' => $logLevel,
                'last_value' => $value,
                'peak_value' => $this->peak($values, $check['mode']),
                'escalated_at' => $logLevel === 'alarm' ? $occurredAt : null,
            ]);
        }
    }

    private function evaluateFault(array $device, string $metric, string $code, Collection $window, Collection $flags, bool $full, ?Logger $log): void
    {
        $current = $window->first()->{$metric};

        if ($log) {
            if ($flags->first()) {
                $log->update(['last_value' => $current, 'message' => $this->faultMessage($device['device_type'], $metric, $current)]);
            } elseif ($full && ! $flags->contains(true)) {
                $log->update(['last_value' => $current, 'cleared_at' => $window->last()->measured_at]);
            }

            return;
        }

        if ($full && $flags->every(fn ($fault) => $fault)) {
            $this->createLog($device, $code, $window->last()->measured_at, [
                'message' => $this->faultMessage($device['device_type'], $metric, $current),
                'status' => 'warning',
                'alarm_type' => 'sensor_fault',
                'last_value' => $current,
            ]);
        }
    }

    private function createLog(array $device, string $code, $occurredAt, array $attributes): void
    {
        Logger::create($attributes + [
            'occurred_at' => $occurredAt,
            'device_type' => $device['device_type'],
            'daop_id' => $device['daop_id'],
            'resor_id' => $device['resor_id'],
            'gardu_id' => $device['gardu_id'],
            'batt_id' => $device['device_type'] === 'battery' ? $device['id'] : null,
            'charger_id' => $device['device_type'] === 'charger' ? $device['id'] : null,
            'alarm_code' => $code,
            'lifecycle_status' => 'open',
            'action' => 'coming',
        ]);
    }

    private function continueLog(Logger $log, array $check, Collection $window, Collection $values, ?string $level, bool $allAlarm, bool $clean, ?float $warn, ?float $alarm): void
    {
        $value = $values->first();
        $changes = ['last_value' => $value];

        if ($level) {
            $changes['peak_value'] = $this->peak(
                is_null($log->peak_value) ? collect([$value]) : collect([$value, (float) $log->peak_value]),
                $check['mode']
            );

            if ($allAlarm && $log->status !== 'alarm') {
                $changes += ['status' => 'alarm', 'alarm_type' => 'alarm', 'escalated_at' => $window->first()->measured_at];
            }

            $changes['message'] = $this->message($check, $value, $this->messageLevel($changes['status'] ?? $log->status, $level), $warn, $alarm);
        } elseif ($clean && $values->every(fn ($v) => $this->isNormal($v, $warn, $alarm, $check['mode']))) {
            // Waktu pulih = sampel normal pertama di jendela debounce
            $changes['cleared_at'] = $window->last()->measured_at;
        }

        $log->update($changes);
    }

    private function level(float $value, ?float $warn, ?float $alarm, string $mode): ?string
    {
        $over = fn (?float $limit) => ! is_null($limit) && ($mode === 'min' ? $value <= $limit : $value >= $limit);

        return $over($alarm) ? 'alarm' : ($over($warn) ? 'warning' : null);
    }

    /**
     * Normal berarti sudah keluar dari batas paling ringan sejauh margin hysteresis.
     */
    private function isNormal(float $value, ?float $warn, ?float $alarm, string $mode): bool
    {
        $limit = $warn ?? $alarm;
        $margin = abs($limit) * (float) config('alarm.hysteresis_pct') / 100;

        return $mode === 'min' ? $value > $limit + $margin : $value < $limit - $margin;
    }

    private function peak(Collection $values, string $mode): float
    {
        return $mode === 'min' ? $values->min() : $values->max();
    }

    // Pesan memakai level yang benar-benar dilewati nilai saat ini, agar kalimatnya tetap benar
    private function messageLevel(string $logLevel, string $currentLevel): string
    {
        return $logLevel === 'alarm' && $currentLevel === 'alarm' ? 'alarm' : 'warning';
    }

    private function message(array $check, float $value, string $level, ?float $warn, ?float $alarm): string
    {
        $threshold = $level === 'alarm' ? $alarm : ($warn ?? $alarm);

        return sprintf(
            '%s %.2f %s %s batas %s %.2f %s',
            $check['label'],
            $value,
            $check['unit'],
            $check['mode'] === 'min' ? 'di bawah' : 'di atas',
            strtoupper($level),
            $threshold,
            $check['unit']
        );
    }

    private function faultMessage(string $device, string $metric, $value): string
    {
        $range = SensorRange::get($device, $metric);

        return sprintf(
            'Sensor %s %s di luar rentang wajar %s s/d %s %s, data diabaikan',
            $range['label'],
            is_null($value) ? 'kosong' : sprintf('%.2f %s', $value, $range['unit']),
            $range['min'],
            $range['max'],
            $range['unit']
        );
    }
}
