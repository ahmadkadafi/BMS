<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportStatsService
{
    public const METRICS = ['temp', 'volt_cell', 'thd', 'soc', 'soh', 'voltage_charge', 'current_charge'];

    /**
     * Statistik harian semua gardu pada satu bulan, dikelompokkan per gardu_id.
     * Hari yang sudah lewat dibaca dari monitoring_daily_stats dan dilengkapi jika belum ada.
     * Hari berjalan selalu dihitung langsung karena datanya masih bertambah.
     */
    public function forMonth(int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone'));
        $end = $start->copy()->endOfMonth();
        $today = now(config('app.timezone'))->startOfDay();

        $stored = DB::table('monitoring_daily_stats')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(function ($row) {
                $row->date = substr($row->date, 0, 10);

                return $row;
            });

        $rows = $stored->all();
        $haveDates = $stored->pluck('date')->unique()->flip();

        foreach ($this->daysWithData($start, $end) as $day) {
            $date = $day->toDateString();

            if ($day->gte($today)) {
                array_push($rows, ...$this->compute($day)->map(fn ($row) => (object) $row)->all());
            } elseif (! $haveDates->has($date)) {
                array_push($rows, ...$this->rollup($day)->map(fn ($row) => (object) $row)->all());
            }
        }

        return collect($rows)->groupBy('gardu_id');
    }

    /**
     * Hitung ulang satu hari dan simpan. Aman dijalankan berulang.
     */
    public function rollup(Carbon $day): Collection
    {
        $rows = $this->compute($day);

        if ($rows->isNotEmpty()) {
            $now = now();
            $columns = collect(self::METRICS)
                ->flatMap(fn ($metric) => [$metric.'_min', $metric.'_max'])
                ->push('updated_at')
                ->all();

            DB::table('monitoring_daily_stats')->upsert(
                $rows->map(fn ($row) => $row + ['created_at' => $now, 'updated_at' => $now])->all(),
                ['gardu_id', 'date'],
                $columns
            );
        }

        return $rows;
    }

    /**
     * Hari di dalam rentang yang berada di antara data monitoring pertama dan terakhir.
     *
     * @return array<int, Carbon>
     */
    private function daysWithData(Carbon $start, Carbon $end): array
    {
        $first = DB::table('battery_monitoring')->min('measured_at');
        $last = DB::table('battery_monitoring')->max('measured_at');

        if (! $first || ! $last) {
            return [];
        }

        $from = $start->copy()->max(Carbon::parse($first, config('app.timezone'))->startOfDay());
        $to = $end->copy()->min(Carbon::parse($last, config('app.timezone'))->endOfDay());

        $days = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $days[] = $day->copy();
        }

        return $days;
    }

    private function compute(Carbon $day): Collection
    {
        $range = [$day->copy()->startOfDay()->toDateTimeString(), $day->copy()->endOfDay()->toDateTimeString()];

        $batteries = DB::table('battery_monitoring as m')
            ->join('battery as b', 'b.id', '=', 'm.battery_id')
            ->whereBetween('m.measured_at', $range)
            ->groupBy('b.gardu_id')
            ->selectRaw('b.gardu_id,
                MIN(m.temp) as temp_min, MAX(m.temp) as temp_max,
                MIN(m.volt) as volt_cell_min, MAX(m.volt) as volt_cell_max,
                MIN(m.thd) as thd_min, MAX(m.thd) as thd_max,
                MIN(m.soc) as soc_min, MAX(m.soc) as soc_max,
                MIN(m.soh) as soh_min, MAX(m.soh) as soh_max')
            ->get()
            ->keyBy('gardu_id');

        $chargers = DB::table('charger_monitoring as m')
            ->join('charger as c', 'c.id', '=', 'm.charger_id')
            ->whereBetween('m.measured_at', $range)
            ->groupBy('c.gardu_id')
            ->selectRaw('c.gardu_id,
                MIN(m.voltage) as voltage_charge_min, MAX(m.voltage) as voltage_charge_max,
                MIN(m.current) as current_charge_min, MAX(m.current) as current_charge_max')
            ->get()
            ->keyBy('gardu_id');

        return $batteries->keys()->merge($chargers->keys())->unique()->values()
            ->map(function ($garduId) use ($batteries, $chargers, $day) {
                $row = ['gardu_id' => $garduId, 'date' => $day->toDateString()];

                foreach (self::METRICS as $metric) {
                    foreach (['_min', '_max'] as $suffix) {
                        $column = $metric.$suffix;
                        $row[$column] = $batteries[$garduId]->{$column} ?? $chargers[$garduId]->{$column} ?? null;
                    }
                }

                return $row;
            });
    }
}
