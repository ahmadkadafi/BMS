<?php

namespace App\Http\Controllers;

use App\Models\BatteryDaily;
use App\Models\Gardu;
use App\Models\IrForecast;
use App\Models\IrForecastRun;
use App\Models\Resor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiPredictionController extends Controller
{
    private const HISTORY_DAYS = 60;

    public function index(Request $request, ?Resor $resor = null)
    {
        $gardu = $this->garduList($resor);
        $selectedGardu = $this->selectedGardu($request, $gardu);
        $horizons = config('ai.horizons');

        $anchors = collect();
        $selectedAnchor = null;
        $cells = collect();
        $forecasts = collect();
        $lastRun = null;

        if ($selectedGardu) {
            $anchors = IrForecast::query()
                ->where('gardu_id', $selectedGardu->id)
                ->select('anchor_date')
                ->distinct()
                ->orderByDesc('anchor_date')
                ->limit(60)
                ->pluck('anchor_date')
                ->map(fn ($date) => $date->toDateString());

            $selectedAnchor = in_array($request->input('anchor'), $anchors->all(), true)
                ? $request->input('anchor')
                : $anchors->first();

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
            'anchors' => $anchors,
            'selectedAnchor' => $selectedAnchor,
            'cells' => $cells,
            'selectedCell' => $selectedCell,
            'setting' => $setting,
            'lastRun' => $lastRun,
            'latest' => $selectedGardu ? $this->latestReading($selectedGardu) : null,
            'quality' => $this->qualitySummary($forecasts),
            'readiness' => $this->readiness($lastRun, $horizons),
            'rows' => $this->cellRows($cells, $forecasts, $horizons, $setting),
            'summary' => $this->horizonSummary($forecasts, $selectedCell, $horizons),
            'chart' => $this->chartData($selectedGardu, $forecasts, $selectedAnchor, $selectedCell),
            'accuracy' => $selectedGardu ? $this->accuracy($selectedGardu, $horizons) : [],
            'statusUrl' => $resor
                ? route('ai-prediction.resor.status', ['resor' => $resor->id, 'gardu_id' => $selectedGardu?->id])
                : route('ai-prediction.status', ['gardu_id' => $selectedGardu?->id]),
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

    private function garduList(?Resor $resor): Collection
    {
        return Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->orderBy('id')
            ->get();
    }

    private function selectedGardu(Request $request, Collection $gardu): ?Gardu
    {
        return $gardu->firstWhere('id', (int) $request->input('gardu_id'))
            ?? $gardu->first(fn (Gardu $g) => in_array($g->kode, config('ai.validated_gardu'), true))
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

            return [
                'label' => $label,
                'r_anchor' => $items->first()?->r_anchor,
                'predictions' => collect($horizons)->mapWithKeys(fn ($h) => [$h => $items[$h] ?? null])->all(),
                'status' => $this->thresholdStatus($last?->r_pred, $setting),
                'quality' => $last?->quality,
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
                'band' => is_null($items->first()->r_high)
                    ? null
                    : (float) $items->first()->r_high - (float) $items->first()->r_pred,
            ]];
        })->all();
    }

    private function chartData(?Gardu $gardu, Collection $forecasts, ?string $anchor, ?int $cell): array
    {
        if (! $gardu || ! $anchor) {
            return ['labels' => [], 'actual' => [], 'forecast' => [], 'low' => [], 'high' => []];
        }

        $start = Carbon::parse($anchor)->subDays(self::HISTORY_DAYS);
        $end = Carbon::parse($anchor)->addDays(max(config('ai.horizons')));

        $actual = BatteryDaily::query()
            ->where('gardu_id', $gardu->id)
            ->when($cell, fn ($q) => $q->where('battery_id', $cell))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, AVG(ir) as ir')
            ->groupBy('date')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->date->toDateString() => round((float) $row->ir, 4)]);

        $scoped = ($cell ? $forecasts->where('battery_id', $cell) : $forecasts)->groupBy('horizon');
        $points = $scoped->mapWithKeys(fn (Collection $items) => [
            $items->first()->target_date->toDateString() => [
                'pred' => round((float) $items->avg('r_pred'), 4),
                'low' => is_null($items->first()->r_low) ? null : round((float) $items->avg('r_low'), 4),
                'high' => is_null($items->first()->r_high) ? null : round((float) $items->avg('r_high'), 4),
            ],
        ]);

        $labels = [];
        $series = ['actual' => [], 'forecast' => [], 'low' => [], 'high' => []];
        $anchorValue = $actual[$anchor] ?? null;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $labels[] = $day->format('d/m/y');
            $series['actual'][] = $actual[$date] ?? null;
            $point = $points[$date] ?? null;
            $seed = $date === $anchor && $points->isNotEmpty() ? $anchorValue : null;
            $series['forecast'][] = $point['pred'] ?? $seed;
            $series['low'][] = $point['low'] ?? ($points->first()['low'] ?? null ? $seed : null);
            $series['high'][] = $point['high'] ?? ($points->first()['high'] ?? null ? $seed : null);
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
