<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Resor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineController extends Controller
{
    public const MAX_RANGE_DAYS = 7;

    // Jumlah titik maksimum per garis; data dirata-rata per interval waktu agar ringan
    private const MAX_POINTS = 300;

    public function index(Request $request, ?Resor $resor = null)
    {
        $timezone = config('app.timezone');
        $today = now($timezone)->startOfDay();

        $gardu = Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->orderBy('id')
            ->get(['id', 'nama']);

        $selectedGarduId = $request->filled('gardu_id') && $gardu->contains('id', (int) $request->gardu_id)
            ? (int) $request->gardu_id
            : ($gardu->contains('id', 1) ? 1 : $gardu->min('id'));

        $selectedChart = $request->input('chart');
        if (! is_string($selectedChart) || ! array_key_exists($selectedChart, $this->chartColumns())) {
            $selectedChart = 'volt';
        }

        $to = $this->parseDate($request->input('to')) ?? $today->copy();
        $from = $this->parseDate($request->input('from')) ?? $to->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $rangeNotice = null;
        if ($from->diffInDays($to) >= self::MAX_RANGE_DAYS) {
            $from = $to->copy()->subDays(self::MAX_RANGE_DAYS - 1);
            $rangeNotice = 'Rentang tanggal maksimal '.self::MAX_RANGE_DAYS.' hari. Filter disesuaikan menjadi '
                .$from->format('d/m/Y').' s/d '.$to->format('d/m/Y').'.';
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $maxDate = $today->toDateString();

        $chartData = [
            'labels' => [],
            'datasets' => [],
            'title' => $this->chartTitle($selectedChart),
            'unit' => $this->chartUnit($selectedChart),
        ];

        $selectedGardu = $gardu->firstWhere('id', $selectedGarduId);
        if ($selectedGardu) {
            $chartData = $this->buildChartData($selectedGardu, $selectedChart, $from, $to->copy()->endOfDay());
        }

        return view('page.line', compact(
            'resor',
            'gardu',
            'selectedGarduId',
            'selectedChart',
            'fromDate',
            'toDate',
            'maxDate',
            'rangeNotice',
            'chartData'
        ));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, config('app.timezone'));

            return $date->toDateString() === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildChartData(Gardu $gardu, string $selectedChart, Carbon $from, Carbon $to): array
    {
        $isCharger = in_array($selectedChart, ['voltage_charger', 'current_charger'], true);
        [$table, $foreignKey, $prefix] = $isCharger
            ? ['charger_monitoring', 'charger_id', 'Charger']
            : ['battery_monitoring', 'battery_id', 'Battery'];
        $column = $this->chartColumns()[$selectedChart];

        $deviceIds = DB::table($isCharger ? 'charger' : 'battery')
            ->where('gardu_id', $gardu->id)
            ->orderBy('id')
            ->pluck('id');

        // Interval dipilih dari langkah yang rapi (1 hari ~5 menit, 7 hari ~1 jam)
        $rangeSeconds = $to->timestamp - $from->timestamp + 1;
        $bucketSeconds = collect([60, 120, 300, 600, 900, 1800, 3600, 7200, 10800, 21600])
            ->first(fn ($step) => $rangeSeconds / $step <= self::MAX_POINTS, 21600);

        $rows = $deviceIds->isEmpty() ? collect() : DB::table($table)
            ->selectRaw(
                $foreignKey.' as device_id, '.$this->bucketExpression('measured_at', $bucketSeconds).' as bucket, AVG('.$column.') as value',
                [$from->format('Y-m-d H:i:s')]
            )
            ->whereIn($foreignKey, $deviceIds)
            ->whereBetween('measured_at', [$from, $to])
            ->groupBy($foreignKey, 'bucket')
            ->get();

        $buckets = $rows->pluck('bucket')->map(fn ($bucket) => (int) $bucket)->unique()->sort()->values();
        $labels = $buckets
            ->map(fn ($bucket) => $from->copy()->addSeconds($bucket * $bucketSeconds)->format('d/m H:i'))
            ->all();

        $datasets = [];
        if ($buckets->isNotEmpty()) {
            $byDevice = $rows->groupBy('device_id');
            $datasets = $deviceIds->values()->map(function ($id, $index) use ($byDevice, $buckets, $prefix) {
                $values = collect($byDevice->get($id, []))
                    ->mapWithKeys(fn ($row) => [(int) $row->bucket => round((float) $row->value, 2)]);

                return [
                    'label' => $prefix.' '.($index + 1),
                    'data' => $buckets->map(fn ($bucket) => $values->get($bucket))->all(),
                ];
            })->all();
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'title' => $this->chartTitle($selectedChart),
            'unit' => $this->chartUnit($selectedChart),
        ];
    }

    // Nomor interval dari selisih detik measured_at terhadap waktu awal (binding), tanpa konversi timezone
    private function bucketExpression(string $column, int $bucketSeconds): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "((strftime('%s', {$column}) - strftime('%s', ?)) / {$bucketSeconds})",
            'pgsql' => "FLOOR(EXTRACT(EPOCH FROM ({$column} - CAST(? AS timestamp))) / {$bucketSeconds})",
            default => "(TIMESTAMPDIFF(SECOND, ?, {$column}) DIV {$bucketSeconds})",
        };
    }

    private function chartColumns(): array
    {
        return [
            'volt' => 'volt',
            'temp' => 'temp',
            'thd' => 'thd',
            'soc' => 'soc',
            'soh' => 'soh',
            'voltage_charger' => 'voltage',
            'current_charger' => 'current',
        ];
    }

    private function chartTitle(string $selectedChart): string
    {
        return match ($selectedChart) {
            'temp' => 'Temperature Cell',
            'thd' => 'IR Cell',
            'soc' => 'SOC Cell',
            'soh' => 'SOH Cell',
            'voltage_charger' => 'Voltage Charger',
            'current_charger' => 'Current Charger',
            default => 'Voltage Cell',
        };
    }

    private function chartUnit(string $selectedChart): string
    {
        return match ($selectedChart) {
            'volt', 'voltage_charger' => 'V',
            'temp' => 'C',
            'thd' => 'mΩ',
            'soc', 'soh' => '%',
            'current_charger' => 'A',
            default => '',
        };
    }
}
