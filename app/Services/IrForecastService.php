<?php

namespace App\Services;

use App\Models\BatteryDaily;
use App\Models\Gardu;
use App\Models\IrForecast;
use App\Models\IrForecastRun;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class IrForecastService
{
    /**
     * Isi battery_daily dari data monitoring, setara dengan daily_long.csv pada tahap 2:
     * IR nilai terakhir hari itu, V dan T rata-rata, arus charger rata-rata dan minimum.
     * Hari berjalan dilewati karena belum lengkap. Dua hari terakhir diproses ulang
     * untuk menampung data yang datang terlambat.
     */
    public function aggregateDaily(): int
    {
        $latest = DB::table('battery_monitoring')->max('measured_at');
        if (! $latest) {
            return 0;
        }

        $lastComplete = Carbon::parse($latest)->subDay()->startOfDay();
        $stored = BatteryDaily::query()->where('samples', '>', 0)->max('date');
        $earliest = DB::table('battery_monitoring')->min('measured_at');
        $day = $stored
            ? Carbon::parse($stored)->subDay()->startOfDay()
            : Carbon::parse($earliest)->startOfDay();

        $rows = 0;
        for (; $day->lte($lastComplete); $day->addDay()) {
            $rows += $this->aggregateDay($day->copy());
        }

        return $rows;
    }

    private function aggregateDay(Carbon $day): int
    {
        $from = $day->copy()->startOfDay()->toDateTimeString();
        $to = $day->copy()->endOfDay()->toDateTimeString();

        $means = DB::table('battery_monitoring as m')
            ->join('battery as b', 'b.id', '=', 'm.battery_id')
            ->whereBetween('m.measured_at', [$from, $to])
            ->groupBy('m.battery_id', 'b.gardu_id')
            ->selectRaw('m.battery_id, b.gardu_id, AVG(m.volt) as volt, AVG(m.temp) as temp, COUNT(*) as samples')
            ->get();

        if ($means->isEmpty()) {
            return 0;
        }

        $lastIr = DB::query()
            ->fromSub(
                DB::table('battery_monitoring')
                    ->whereBetween('measured_at', [$from, $to])
                    ->selectRaw('battery_id, thd, ROW_NUMBER() OVER (PARTITION BY battery_id ORDER BY measured_at DESC, id DESC) as rn'),
                'ranked'
            )
            ->where('rn', 1)
            ->pluck('thd', 'battery_id');

        $chargers = DB::table('charger_monitoring as m')
            ->join('charger as c', 'c.id', '=', 'm.charger_id')
            ->whereBetween('m.measured_at', [$from, $to])
            ->groupBy('c.gardu_id')
            ->selectRaw('c.gardu_id, AVG(m.voltage) as v_charger, AVG(m.current) as i_mean, MIN(m.current) as i_min')
            ->get()
            ->keyBy('gardu_id');

        $now = now();
        $rows = $means->map(fn ($row) => [
            'battery_id' => $row->battery_id,
            'gardu_id' => $row->gardu_id,
            'date' => $day->toDateString(),
            'ir' => $lastIr[$row->battery_id],
            'volt' => $row->volt,
            'temp' => $row->temp,
            'v_charger' => $chargers[$row->gardu_id]->v_charger ?? null,
            'i_mean' => $chargers[$row->gardu_id]->i_mean ?? null,
            'i_min' => $chargers[$row->gardu_id]->i_min ?? null,
            'samples' => $row->samples,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        BatteryDaily::upsert(
            $rows,
            ['battery_id', 'date'],
            ['gardu_id', 'ir', 'volt', 'temp', 'v_charger', 'i_mean', 'i_min', 'samples', 'updated_at']
        );

        return count($rows);
    }

    /**
     * Forecast satu gardu dari hari lengkap terakhir, atau dari $asOf untuk replay historis.
     * Mengembalikan null jika anchor tersebut sudah diproses atau belum ada data.
     */
    public function forecastGardu(Gardu $gardu, ?string $asOf = null, bool $force = false): ?IrForecastRun
    {
        $anchor = BatteryDaily::query()
            ->where('gardu_id', $gardu->id)
            ->when($asOf, fn ($q) => $q->where('date', '<=', $asOf))
            ->max('date');

        if (! $anchor) {
            return null;
        }

        $anchor = Carbon::parse($anchor)->toDateString();
        $alreadyDone = IrForecastRun::query()
            ->where('gardu_id', $gardu->id)
            ->whereDate('anchor_date', $anchor)
            ->where('status', '!=', 'failed')
            ->exists();

        if ($alreadyDone && ! $force) {
            return null;
        }

        try {
            $result = $this->runModel($this->payload($gardu, $anchor));
        } catch (RuntimeException $e) {
            return IrForecastRun::create([
                'gardu_id' => $gardu->id,
                'anchor_date' => $anchor,
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 2000),
            ]);
        }

        return DB::transaction(function () use ($gardu, $anchor, $result) {
            $now = now();
            $rows = collect($result['forecasts'])->map(fn (array $f) => [
                'battery_id' => $f['battery_id'],
                'gardu_id' => $gardu->id,
                'anchor_date' => $f['anchor_date'],
                'horizon' => $f['horizon'],
                'target_date' => $f['target_date'],
                'model' => $f['model'],
                'r_anchor' => $f['r_anchor'],
                'r_pred' => $f['r_pred'],
                'r_low' => $f['r_low'],
                'r_high' => $f['r_high'],
                'seed_std' => $f['seed_std'],
                'flat_days' => $f['flat_days'],
                'max_z' => $f['max_z'],
                'quality' => $f['quality'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($rows->isNotEmpty()) {
                IrForecast::upsert(
                    $rows->all(),
                    ['battery_id', 'anchor_date', 'horizon'],
                    ['target_date', 'model', 'r_anchor', 'r_pred', 'r_low', 'r_high', 'seed_std', 'flat_days', 'max_z', 'quality', 'updated_at']
                );
            }

            return IrForecastRun::create([
                'gardu_id' => $gardu->id,
                'anchor_date' => $anchor,
                'status' => empty($result['skipped']) ? 'ok' : 'partial',
                'n_forecast' => $rows->count(),
                'skipped' => $result['skipped'],
            ]);
        });
    }

    private function payload(Gardu $gardu, string $anchor): array
    {
        $validated = in_array($gardu->kode, config('ai.validated_gardu'), true);
        $from = Carbon::parse($anchor)->subDays(config('ai.history_days'))->toDateString();

        return BatteryDaily::query()
            ->where('gardu_id', $gardu->id)
            ->whereBetween('date', [$from, $anchor])
            ->orderBy('date')
            ->get()
            ->groupBy('battery_id')
            ->map(fn (Collection $days, int $batteryId) => [
                'battery_id' => $batteryId,
                'validated' => $validated,
                'anchor' => $anchor,
                'days' => $days->map(fn (BatteryDaily $d) => [
                    'date' => $d->date->toDateString(),
                    'r' => (float) $d->ir,
                    'v' => (float) $d->volt,
                    't' => (float) $d->temp,
                    'v_charger' => is_null($d->v_charger) ? null : (float) $d->v_charger,
                    'i_mean' => is_null($d->i_mean) ? null : (float) $d->i_mean,
                    'i_min' => is_null($d->i_min) ? null : (float) $d->i_min,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function runModel(array $cells): array
    {
        $process = Process::path(base_path('ml'))
            ->timeout(config('ai.timeout'))
            ->input(json_encode(['cells' => $cells]))
            ->run([config('ai.python'), config('ai.script')]);

        if ($process->failed()) {
            throw new RuntimeException(trim($process->errorOutput()) ?: 'Proses forecast gagal.');
        }

        $result = json_decode($process->output(), true);
        if (! is_array($result) || ! isset($result['forecasts'], $result['skipped'])) {
            throw new RuntimeException('Keluaran forecast tidak valid.');
        }

        return $result;
    }
}
