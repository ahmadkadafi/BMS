<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Resor;
use App\Models\Gardu;
use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TableController extends Controller
{
    public function index(Request $request, Resor $resor)
    {
        $gardu = Gardu::where('resor_id', $resor->id)
            ->with(['batteries:id,gardu_id', 'chargers:id,gardu_id'])
            ->orderBy('id')
            ->get();

        $defaultGarduId = $gardu->min('id');
        $selectedGarduId = $request->filled('gardu_id')
            ? (int) $request->gardu_id
            : $defaultGarduId;

        $garduData = $selectedGarduId
            ? $gardu->where('id', $selectedGarduId)->values()
            : collect();

        $garduIds = $garduData->pluck('id');
        $maxBatt = $garduData->max(fn ($g) => $g->batteries->count()) ?? 0;
        $maxCharger = $garduData->max(fn ($g) => $g->chargers->count()) ?? 0;
        $selectedTable = $request->input('table', 'volt');
        $allRows = $this->buildRows($request, $garduData, $garduIds);

        if ($request->boolean('export')) {
            return $this->exportCsv($resor, $allRows, $selectedTable, $maxBatt);
        }

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageRows = $allRows->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $rows = new LengthAwarePaginator(
            $pageRows,
            $allRows->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('page.table', compact(
            'resor',
            'gardu',
            'selectedGarduId',
            'rows',
            'selectedTable',
            'maxBatt',
            'maxCharger'
        ));
    }

    private function buildRows(Request $request, $garduData, $garduIds)
    {
        $batteryTimes = BatteryMonitoring::query()
            ->select('measured_at')
            ->whereHas('battery', fn ($q) => $q->whereIn('gardu_id', $garduIds))
            ->when($request->filled('from'), fn ($q) =>
                $q->whereDate('measured_at', '>=', $request->from)
            )
            ->when($request->filled('to'), fn ($q) =>
                $q->whereDate('measured_at', '<=', $request->to)
            );

        $chargerTimes = ChargerMonitoring::query()
            ->select('measured_at')
            ->whereHas('charger', fn ($q) => $q->whereIn('gardu_id', $garduIds))
            ->when($request->filled('from'), fn ($q) =>
                $q->whereDate('measured_at', '>=', $request->from)
            )
            ->when($request->filled('to'), fn ($q) =>
                $q->whereDate('measured_at', '<=', $request->to)
            );

        $snapshotTimes = DB::query()
            ->fromSub($batteryTimes->union($chargerTimes), 'snapshots')
            ->select('measured_at')
            ->distinct()
            ->orderByDesc('measured_at')
            ->limit(500)
            ->get();

        $allRows = collect();

        foreach ($snapshotTimes as $time) {
            $batteryRows = BatteryMonitoring::with(['battery:id,gardu_id'])
                ->where('measured_at', $time->measured_at)
                ->whereHas('battery', fn ($q) => $q->whereIn('gardu_id', $garduIds))
                ->orderBy('battery_id')
                ->get()
                ->groupBy(fn ($row) => optional($row->battery)->gardu_id);

            $chargerRows = ChargerMonitoring::with(['charger:id,gardu_id'])
                ->where('measured_at', $time->measured_at)
                ->whereHas('charger', fn ($q) => $q->whereIn('gardu_id', $garduIds))
                ->orderBy('charger_id')
                ->get()
                ->groupBy(fn ($row) => optional($row->charger)->gardu_id);

            foreach ($garduData as $g) {
                $allRows->push([
                    'time' => $time->measured_at,
                    'gardu' => $g->nama,
                    'batteries' => ($batteryRows->get($g->id) ?? collect())->values(),
                    'chargers' => ($chargerRows->get($g->id) ?? collect())->values(),
                ]);
            }
        }

        return $allRows;
    }

    private function exportCsv(Resor $resor, $rows, string $selectedTable, int $maxBatt): StreamedResponse
    {
        $columns = ['No', 'Date', 'Time'];
        if ($selectedTable === 'charger') {
            $columns[] = 'Volt Charge';
            $columns[] = 'Current Charge';
        } else {
            $columns[] = 'Lokasi';
            for ($i = 1; $i <= $maxBatt; $i++) {
                $columns[] = 'Batt ' . $i;
            }
        }

        $filename = sprintf(
            'table-%s-%s.csv',
            $resor->id,
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($rows, $selectedTable, $columns, $maxBatt) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            foreach ($rows->values() as $index => $row) {
                $time = $row['time'] instanceof Carbon
                    ? $row['time']
                    : Carbon::parse($row['time']);

                $line = [
                    $index + 1,
                    $time->format('d/m/Y'),
                    $time->format('H:i:s'),
                ];

                if ($selectedTable === 'charger') {
                    $line[] = collect($row['chargers'])
                        ->pluck('voltage')
                        ->filter(fn ($value) => $value !== null && $value !== '')
                        ->implode(', ') ?: '-';
                    $line[] = collect($row['chargers'])
                        ->pluck('current')
                        ->filter(fn ($value) => $value !== null && $value !== '')
                        ->implode(', ') ?: '-';
                } else {
                    $line[] = $row['gardu'];
                    for ($i = 0; $i < $maxBatt; $i++) {
                        $battery = $row['batteries'][$i] ?? null;
                        $line[] = $battery?->{$selectedTable} ?? '-';
                    }
                }

                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
