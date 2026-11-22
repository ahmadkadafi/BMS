<?php

namespace App\Http\Controllers;

use App\Models\BatteryDaily;
use App\Models\Gardu;
use App\Models\IrForecast;
use App\Models\IrForecastRun;
use App\Models\Resor;
use App\Services\AlarmEtaService;
use App\Support\DateRange;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiPredictionController extends Controller
{
    // Rentang tanggal anchor pada halaman forecast, cukup untuk dua bulan kalender penuh
    public const FORECAST_MAX_DAYS = 62;

    public const TABULATION_MAX_DAYS = 45;

    private const TABULATION_PER_PAGE = 50;

    public function index(Request $request, ?Resor $resor = null)
    {
        $gardu = $this->garduList($resor);
        $selectedGardu = $this->selectedGardu($request, $gardu);
        $horizons = config('ai.horizons');

        $selectedAnchor = null;
        $cells = collect();
        $forecasts = collect();
        $lastRun = null;

        // Default: dua bulan sampai anchor terakhir gardu, agar prediksi lama tetap tampil
        if (! $request->filled('from') && ! $request->filled('to')) {
            $latestAnchor = $selectedGardu
                ? IrForecast::query()->where('gardu_id', $selectedGardu->id)->max('anchor_date')
                : null;
            $to = $latestAnchor
                ? Carbon::parse($latestAnchor, config('app.timezone'))
                : now(config('app.timezone'))->startOfDay();
            $request->merge([
                'from' => $to->copy()->subDays(self::FORECAST_MAX_DAYS - 1)->toDateString(),
                'to' => $to->toDateString(),
            ]);
        }
        $range = DateRange::fromRequest($request, self::FORECAST_MAX_DAYS);

        if ($selectedGardu) {
            // Kartu ringkasan dan tabel per sel memakai anchor terakhir di dalam rentang
            $selectedAnchor = IrForecast::query()
                ->where('gardu_id', $selectedGardu->id)
                ->whereBetween('anchor_date', [$range->fromDate(), $range->toDate()])
                ->max('anchor_date');
            $selectedAnchor = $selectedAnchor ? Carbon::parse($selectedAnchor)->toDateString() : null;

            $cells = $selectedGardu->batteries()->orderBy('id')->pluck('id')
                ->mapWithKeys(fn ($id, $index) => [$id => 'Sel '.($index + 1)]);

            $forecasts = IrForecast::query()
                ->where('gardu_id', $selectedGardu->id)
                ->whereDate('anchor_date', $selectedAnchor)
                ->get();

            $lastRun = IrForecastRun::query()
                ->where('gardu_id', $selectedGardu->id)
                ->latest('id')
                ->first();
        }

        $selectedCell = $cells->has((int) $request->input('cell')) ? (int) $request->input('cell') : null;
        $setting = $selectedGardu?->batterySetting;

        return view('page.ai_prediction', [
            'resor' => $resor,
            'gardu' => $gardu,
            'selectedGardu' => $selectedGardu,
            'isValidated' => in_array($selectedGardu?->kode, config('ai.validated_gardu'), true),
            'backtest' => $this->backtest($selectedGardu),
            'horizons' => $horizons,
            'selectedAnchor' => $selectedAnchor,
            'fromDate' => $range->fromDate(),
            'toDate' => $range->toDate(),
            'maxDate' => DateRange::maxDate(),
            'rangeNotice' => $range->notice,
            'cells' => $cells,
            'selectedCell' => $selectedCell,
            'setting' => $setting,
            'lastRun' => $lastRun,
            'latest' => $selectedGardu ? $this->latestReading($selectedGardu) : null,
            'quality' => $this->qualitySummary($forecasts),
            'readiness' => $this->readiness($lastRun, $horizons),
            'rows' => $this->cellRows($cells, $forecasts, $horizons, $setting),
            'summary' => $this->horizonSummary($forecasts, $selectedCell, $horizons),
            'chart' => $this->chartData($selectedGardu, $range, $selectedAnchor, $selectedCell, $horizons),
            'accuracy' => $selectedGardu ? $this->accuracy($selectedGardu, $horizons) : [],
            'statusUrl' => $resor
                ? route('ai-prediction.resor.status', ['resor' => $resor->id, 'gardu_id' => $selectedGardu?->id])
                : route('ai-prediction.status', ['gardu_id' => $selectedGardu?->id]),
        ]);
    }

    /**
     * Tabulasi prediksi vs aktual: satu baris = satu prediksi sel, dibandingkan dengan IR harian
     * pada tanggal target. Filter tanggal memakai tanggal target.
     */
    public function tabulation(Request $request, ?Resor $resor = null)
    {
        $gardu = $this->garduList($resor);
        $selectedGardu = $this->selectedGardu($request, $gardu);
        $horizons = config('ai.horizons');
        $selectedHorizon = in_array((int) $request->input('horizon'), $horizons, true) ? (int) $request->input('horizon') : null;

        $cells = $selectedGardu
            ? $selectedGardu->batteries()->orderBy('id')->pluck('id')->mapWithKeys(fn ($id, $index) => [$id => 'Sel '.($index + 1)])
            : collect();
        $selectedCell = $cells->has((int) $request->input('cell')) ? (int) $request->input('cell') : null;

        if (! $request->filled('from') && ! $request->filled('to')) {
            $today = now(config('app.timezone'));
            $request->merge([
                'from' => $today->copy()->subDays(self::TABULATION_MAX_DAYS - 1)->toDateString(),
                'to' => $today->toDateString(),
            ]);
        }
        $range = DateRange::fromRequest($request, self::TABULATION_MAX_DAYS);

        $query = $this->tabulationQuery($selectedGardu, $range, $selectedHorizon, $selectedCell);

        if ($request->boolean('export')) {
            return $this->exportTabulation($query, $cells, $selectedGardu, $range);
        }

        $rows = (clone $query)->paginate(self::TABULATION_PER_PAGE)->withQueryString();
        $rows->setCollection($rows->getCollection()->map(fn ($row) => $this->tabulationRow($row, $cells)));

        return view('page.ai_tabulation', [
            'resor' => $resor,
            'gardu' => $gardu,
            'selectedGardu' => $selectedGardu,
            'cells' => $cells,
            'selectedCell' => $selectedCell,
            'horizons' => $horizons,
            'selectedHorizon' => $selectedHorizon,
            'fromDate' => $range->fromDate(),
            'toDate' => $range->toDate(),
            'maxDate' => DateRange::maxDate(),
            'rangeNotice' => $range->notice,
            'rows' => $rows,
            'metrics' => $this->tabulationMetrics($query, $horizons),
            'pending' => (clone $query)->reorder()->whereNull('d.ir')->count(),
        ]);
    }

    public function status(Request $request, ?Resor $resor = null): JsonResponse
    {
        $selectedGardu = $this->selectedGardu($request, $this->garduList($resor));
        if (! $selectedGardu) {
            return response()->json(['version' => null, 'latest' => null]);
        }

        return response()->json([
            'version' => IrForecastRun::query()->where('gardu_id', $selectedGardu->id)->max('id'),
            'latest' => $this->latestReading($selectedGardu),
        ]);
    }

    private function tabulationQuery(?Gardu $gardu, DateRange $range, ?int $horizon, ?int $cell): Builder
    {
        return DB::table('ir_forecast as f')
            ->leftJoin('battery_daily as d', function ($join) {
                $join->on('d.battery_id', '=', 'f.battery_id')
                    ->on('d.date', '=', 'f.target_date');
            })
            ->where('f.gardu_id', $gardu?->id ?? 0)
            ->whereBetween('f.target_date', [$range->fromDate(), $range->toDate()])
            ->when($horizon, fn ($q) => $q->where('f.horizon', $horizon))
            ->when($cell, fn ($q) => $q->where('f.battery_id', $cell))
            ->orderByDesc('f.target_date')
            ->orderBy('f.horizon')
            ->orderBy('f.battery_id')
            ->select('f.battery_id', 'f.anchor_date', 'f.target_date', 'f.horizon', 'f.model', 'f.r_anchor', 'f.r_pred', 'f.quality', 'd.ir as r_actual');
    }

    private function tabulationRow(object $row, Collection $cells): array
    {
        $actual = is_null($row->r_actual) ? null : (float) $row->r_actual;
        $pred = (float) $row->r_pred;
        $error = is_null($actual) ? null : $pred - $actual;

        return [
            'cell' => $cells[$row->battery_id] ?? 'Sel ?',
            'anchor_date' => Carbon::parse($row->anchor_date),
            'target_date' => Carbon::parse($row->target_date),
            'horizon' => (int) $row->horizon,
            'model' => $row->model,
            'r_anchor' => (float) $row->r_anchor,
            'r_pred' => $pred,
            'r_actual' => $actual,
            'error' => $error,
            'error_pct' => is_null($error) || $actual == 0.0 ? null : abs($error) / $actual * 100,
            'quality' => $row->quality,
        ];
    }

    /**
     * MAE, RMSE, MAPE model dan MAE persistence per horizon, hanya untuk prediksi berkualitas
     * baik yang nilai aktualnya sudah ada.
     */
    private function tabulationMetrics(Builder $query, array $horizons): array
    {
        $rows = (clone $query)
            ->reorder()
            ->whereNotNull('d.ir')
            ->where('f.quality', 'ok')
            ->groupBy('f.horizon')
            ->select(DB::raw('f.horizon, COUNT(*) as n,
                AVG(ABS(f.r_pred - d.ir)) as mae,
                AVG((f.r_pred - d.ir) * (f.r_pred - d.ir)) as mse,
                AVG(CASE WHEN d.ir <> 0 THEN ABS(f.r_pred - d.ir) / d.ir END) * 100 as mape,
                AVG(ABS(f.r_anchor - d.ir)) as mae_persist'))
            ->get()
            ->keyBy('horizon');

        return collect($horizons)->mapWithKeys(function ($h) use ($rows) {
            $row = $rows[$h] ?? null;
            $mae = $row ? (float) $row->mae : null;
            $persist = $row ? (float) $row->mae_persist : null;

            return [$h => [
                'n' => (int) ($row->n ?? 0),
                'mae' => $mae,
                'rmse' => $row ? sqrt((float) $row->mse) : null,
                'mape' => $row && ! is_null($row->mape) ? (float) $row->mape : null,
                'mae_persist' => $persist,
                'skill' => $persist ? 100 * (1 - $mae / $persist) : null,
            ]];
        })->all();
    }

    private function exportTabulation(Builder $query, Collection $cells, ?Gardu $gardu, DateRange $range): StreamedResponse
    {
        $filename = sprintf(
            'tabulasi-prediksi-ir-%s-%s_%s.csv',
            $gardu ? Str::slug($gardu->nama) : 'gardu',
            $range->from->format('Ymd'),
            $range->to->format('Ymd')
        );

        return response()->streamDownload(function () use ($query, $cells, $gardu) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'No', 'Lokasi', 'Tanggal Anchor', 'Tanggal Target', 'Horizon', 'Sel', 'Model',
                'IR Anchor (mOhm)', 'IR Prediksi (mOhm)', 'IR Aktual (mOhm)', 'Selisih (mOhm)', 'Error (%)', 'Kualitas',
            ]);

            $number = 0;
            (clone $query)->chunk(500, function ($chunk) use ($handle, $cells, $gardu, &$number) {
                foreach ($chunk as $item) {
                    $row = $this->tabulationRow($item, $cells);
                    fputcsv($handle, [
                        ++$number,
                        $gardu?->nama,
                        $row['anchor_date']->format('d/m/Y'),
                        $row['target_date']->format('d/m/Y'),
                        'H+'.$row['horizon'],
                        $row['cell'],
                        $row['model'],
                        round($row['r_anchor'], 3),
                        round($row['r_pred'], 4),
                        $row['r_actual'] ?? '-',
                        is_null($row['error']) ? '-' : round($row['error'], 4),
                        is_null($row['error_pct']) ? '-' : round($row['error_pct'], 2),
                        $row['quality'],
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function garduList(?Resor $resor): Collection
    {
        return Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->orderBy('id')
            ->get();
    }

    private function selectedGardu(Request $request, Collection $gardu): ?Gardu
    {
        // Tampilan awal (tanpa gardu_id) ke gardu dengan ID terkecil (ID 1).
        return $gardu->firstWhere('id', (int) $request->input('gardu_id'))
            ?? $gardu->first();
    }

    /**
     * Skill model terhadap persistence per horizon dari ml/backtest.py (persen, null jika
     * semua window ditolak quality gate). Horizon tanpa baris berarti tidak bisa diuji.
     */
    private function backtest(?Gardu $gardu): array
    {
        $path = config('ai.backtest');
        if (! $gardu || ! is_file($path)) {
            return [];
        }

        $lines = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_shift($lines);

        return collect($lines)
            ->map(fn (array $line) => array_combine($header, $line))
            ->where('gardu', $gardu->kode)
            ->mapWithKeys(fn (array $row) => [
                (int) $row['h'] => $row['skill_pct'] === '' ? null : (float) $row['skill_pct'],
            ])
            ->all();
    }

    private function latestReading(Gardu $gardu): ?array
    {
        $measuredAt = DB::table('battery_monitoring as m')
            ->join('battery as b', 'b.id', '=', 'm.battery_id')
            ->where('b.gardu_id', $gardu->id)
            ->max('m.measured_at');

        if (! $measuredAt) {
            return null;
        }

        $ir = DB::table('battery_monitoring as m')
            ->join('battery as b', 'b.id', '=', 'm.battery_id')
            ->where('b.gardu_id', $gardu->id)
            ->where('m.measured_at', $measuredAt)
            ->avg('m.thd');

        $time = Carbon::parse($measuredAt, config('app.timezone'));

        return [
            'measured_at' => $time->format('d/m/Y H:i'),
            'ir' => round((float) $ir, 2),
            'stale_days' => (int) $time->diffInDays(now(config('app.timezone'))),
        ];
    }

    private function qualitySummary(Collection $forecasts): array
    {
        return [
            'ir_frozen' => $forecasts->where('quality', 'ir_frozen')->pluck('battery_id')->unique()->count(),
            'ood' => $forecasts->where('quality', 'ood')->pluck('battery_id')->unique()->count(),
            'flat_days' => (int) $forecasts->max('flat_days'),
            'max_z' => round((float) $forecasts->max('max_z'), 1),
        ];
    }

    private function readiness(?IrForecastRun $run, array $horizons): array
    {
        if (! $run) {
            return [];
        }

        $skipped = collect($run->skipped ?? [])->keyBy('horizon');

        return collect($horizons)
            ->filter(fn ($h) => $skipped->has($h))
            ->map(function ($h) use ($skipped, $run) {
                $item = $skipped[$h];

                return [
                    'horizon' => $h,
                    'model' => $item['model'],
                    'reason' => $item['reason'],
                    'required' => $item['required'],
                    'available' => $item['available'],
                    'ready_at' => $item['reason'] === 'insufficient_history'
                        ? $run->anchor_date->copy()->addDays($item['required'] - $item['available'])->format('d/m/Y')
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    private function cellRows(Collection $cells, Collection $forecasts, array $horizons, $setting): array
    {
        $byCell = $forecasts->groupBy('battery_id');

        return $cells->map(function (string $label, int $batteryId) use ($byCell, $horizons, $setting) {
            $items = ($byCell[$batteryId] ?? collect())->keyBy('horizon');
            $last = $items->sortBy('horizon')->last();

            $first = $items->first();
            $reliable = $first && $items->every(fn ($f) => $f->quality === 'ok');
            $eta = collect(['warning' => $setting?->thd_warn, 'alarm' => $setting?->thd_alarm])
                ->filter(fn ($limit) => $reliable && ! is_null($limit))
                ->map(fn ($limit) => app(AlarmEtaService::class)->forecastCrossing(
                    Carbon::parse($first->anchor_date),
                    (float) $first->r_anchor,
                    $items->values(),
                    (float) $limit
                ))
                ->all();

            return [
                'label' => $label,
                'r_anchor' => $first?->r_anchor,
                'predictions' => collect($horizons)->mapWithKeys(fn ($h) => [$h => $items[$h] ?? null])->all(),
                'status' => $this->thresholdStatus($last?->r_pred, $setting),
                'quality' => $last?->quality,
                'eta' => $eta,
            ];
        })->values()->all();
    }

    private function thresholdStatus($value, $setting): string
    {
        if (is_null($value)) {
            return '-';
        }
        if (is_null($setting?->thd_warn) && is_null($setting?->thd_alarm)) {
            return 'Batas belum diatur';
        }
        if (! is_null($setting->thd_alarm) && $value >= $setting->thd_alarm) {
            return 'Alarm';
        }
        if (! is_null($setting->thd_warn) && $value >= $setting->thd_warn) {
            return 'Warning';
        }

        return 'Normal';
    }

    private function horizonSummary(Collection $forecasts, ?int $cell, array $horizons): array
    {
        $scoped = $cell ? $forecasts->where('battery_id', $cell) : $forecasts;

        return collect($horizons)->mapWithKeys(function ($h) use ($scoped) {
            $items = $scoped->where('horizon', $h);
            if ($items->isEmpty()) {
                return [$h => null];
            }

            return [$h => [
                'target' => $items->first()->target_date->format('d/m/Y'),
                'model' => $items->first()->model,
                'r_pred' => (float) $items->avg('r_pred'),
                'delta' => (float) $items->avg('r_pred') - (float) $items->avg('r_anchor'),
            ]];
        })->all();
    }

    /**
     * IR aktual harian dan prediksi per horizon menurut tanggal target, untuk semua anchor di
     * dalam rentang. Sumbu waktu diperpanjang sampai target terjauh dari anchor terakhir.
     */
    private function chartData(?Gardu $gardu, DateRange $range, ?string $anchor, ?int $cell, array $horizons): array
    {
        if (! $gardu || ! $anchor) {
            return ['labels' => [], 'actual' => [], 'forecast' => []];
        }

        $start = $range->from->copy();
        $end = Carbon::parse($anchor)->addDays(max($horizons));

        $actual = BatteryDaily::query()
            ->where('gardu_id', $gardu->id)
            ->when($cell, fn ($q) => $q->where('battery_id', $cell))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, AVG(ir) as ir')
            ->groupBy('date')
            ->get()
            ->mapWithKeys(fn ($row) => [Carbon::parse($row->date)->toDateString() => round((float) $row->ir, 4)]);

        $predicted = DB::table('ir_forecast')
            ->where('gardu_id', $gardu->id)
            ->when($cell, fn ($q) => $q->where('battery_id', $cell))
            ->whereBetween('anchor_date', [$range->fromDate(), $range->toDate()])
            ->selectRaw('horizon, target_date, AVG(r_pred) as r_pred')
            ->groupBy('horizon', 'target_date')
            ->get()
            ->groupBy('horizon')
            ->map(fn (Collection $items) => $items->mapWithKeys(fn ($row) => [
                Carbon::parse($row->target_date)->toDateString() => round((float) $row->r_pred, 4),
            ]));

        $labels = [];
        $series = ['actual' => [], 'forecast' => array_fill_keys($horizons, [])];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $labels[] = $day->format('d/m/y');
            $series['actual'][] = $actual[$date] ?? null;
            foreach ($horizons as $h) {
                $series['forecast'][$h][] = $predicted[$h][$date] ?? null;
            }
        }

        return ['labels' => $labels] + $series;
    }

    private function accuracy(Gardu $gardu, array $horizons): array
    {
        $rows = IrForecast::query()
            ->from('ir_forecast as f')
            ->join('battery_daily as d', function ($join) {
                $join->on('d.battery_id', '=', 'f.battery_id')
                    ->on('d.date', '=', 'f.target_date');
            })
            ->where('f.gardu_id', $gardu->id)
            ->where('f.quality', 'ok')
            ->selectRaw('f.horizon, COUNT(*) as n, AVG(ABS(f.r_pred - d.ir)) as mae, AVG(ABS(f.r_anchor - d.ir)) as mae_persist')
            ->groupBy('f.horizon')
            ->get()
            ->keyBy('horizon');

        return collect($horizons)->mapWithKeys(fn ($h) => [$h => [
            'n' => (int) ($rows[$h]->n ?? 0),
            'mae' => isset($rows[$h]) ? (float) $rows[$h]->mae : null,
            'mae_persist' => isset($rows[$h]) ? (float) $rows[$h]->mae_persist : null,
        ]])->all();
    }
}
