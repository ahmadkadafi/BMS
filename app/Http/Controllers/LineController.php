<?php

namespace App\Http\Controllers;

use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use App\Models\Gardu;
use App\Models\Resor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LineController extends Controller
{
    public function index(Request $request, ?Resor $resor = null)
    {
        $appNow = now(config('app.timezone'));

        $gardu = Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->with(['batteries:id,gardu_id', 'chargers:id,gardu_id'])
            ->orderBy('id')
            ->get();

        $selectedGarduId = $request->filled('gardu_id')
            ? (int) $request->gardu_id
            : ($gardu->contains('id', 1) ? 1 : $gardu->min('id'));

        $selectedChart = $request->input('chart', 'volt');
        $toDate = $request->input('to', $appNow->toDateString());
        $fromDate = $request->input('from', $appNow->toDateString());

        $selectedGardu = $gardu->firstWhere('id', $selectedGarduId);

        $chartData = [
            'labels' => [],
            'datasets' => [],
            'title' => $this->chartTitle($selectedChart),
            'unit' => $this->chartUnit($selectedChart),
        ];

        if ($selectedGardu) {
            $from = Carbon::parse($fromDate, config('app.timezone'))->startOfDay();
            $to = Carbon::parse($toDate, config('app.timezone'))->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                $fromDate = $from->toDateString();
                $toDate = $to->toDateString();
            }

            $chartData = $this->buildChartData($selectedGardu, $selectedChart, $from, $to);
        }

        return view('page.line', compact(
            'resor',
            'gardu',
            'selectedGarduId',
            'selectedChart',
            'fromDate',
            'toDate',
            'chartData'
        ));
    }

    private function buildChartData(Gardu $gardu, string $selectedChart, Carbon $from, Carbon $to): array
    {
        if (in_array($selectedChart, ['voltage_charger', 'current_charger'], true)) {
            $metric = $selectedChart === 'voltage_charger' ? 'voltage' : 'current';

            $datasets = $gardu->chargers->values()->map(function ($charger, $index) use ($from, $to, $metric) {
                $points = $charger->monitorings()
                    ->whereBetween('measured_at', [$from, $to])
                    ->orderBy('measured_at')
                    ->limit(300)
                    ->get(['measured_at', $metric])
                    ->map(fn ($row) => [
                        'x' => Carbon::parse($row->measured_at)->timezone(config('app.timezone'))->format('d/m H:i'),
                        'y' => $row->{$metric},
                    ])
                    ->values();

                return [
                    'label' => 'Charger ' . ($index + 1),
                    'data' => $points,
                ];
            })->all();

            return [
                'labels' => [],
                'datasets' => $datasets,
                'title' => $this->chartTitle($selectedChart),
                'unit' => $this->chartUnit($selectedChart),
            ];
        }

        $datasets = $gardu->batteries->values()->map(function ($battery, $index) use ($from, $to, $selectedChart) {
            $points = $battery->monitorings()
                ->whereBetween('measured_at', [$from, $to])
                ->orderBy('measured_at')
                ->limit(300)
                ->get(['measured_at', $selectedChart])
                ->map(fn ($row) => [
                    'x' => Carbon::parse($row->measured_at)->timezone(config('app.timezone'))->format('d/m H:i'),
                    'y' => $row->{$selectedChart},
                ])
                ->values();

            return [
                'label' => 'Battery ' . ($index + 1),
                'data' => $points,
            ];
        })->all();

        return [
            'labels' => [],
            'datasets' => $datasets,
            'title' => $this->chartTitle($selectedChart),
            'unit' => $this->chartUnit($selectedChart),
        ];
    }

    private function chartTitle(string $selectedChart): string
    {
        return match ($selectedChart) {
            'temp' => 'Temperature Cell',
            'thd' => 'THD Cell',
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
            'thd', 'soc', 'soh' => '%',
            'current_charger' => 'A',
            default => '',
        };
    }
}
