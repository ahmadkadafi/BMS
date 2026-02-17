<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resor;
use App\Models\Gardu;
use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    public function index(Request $request, Resor $resor)
    {
        $gardu = Gardu::where('resor_id', $resor->id)
            ->with(['batteries:id,gardu_id', 'chargers:id,gardu_id'])
            ->orderBy('nama')
            ->get();

        $garduData = $request->filled('gardu_id')
            ? $gardu->where('id', (int) $request->gardu_id)->values()
            : $gardu;

        $garduIds = $garduData->pluck('id');
        $maxBatt = $garduData->max(fn ($g) => $g->batteries->count()) ?? 0;
        $maxCharger = $garduData->max(fn ($g) => $g->chargers->count()) ?? 0;

        /*
        |--------------------------------------------------------------------------
        | SNAPSHOT TIME (PAGINATION)
        |--------------------------------------------------------------------------
        */
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

        $times = DB::query()
            ->fromSub($batteryTimes->union($chargerTimes), 'snapshots')
            ->select('measured_at')
            ->distinct()
            ->orderByDesc('measured_at')
            ->paginate(5)
            ->withQueryString(); // 5 snapshot per halaman

        /*
        |--------------------------------------------------------------------------
        | DATA PER SNAPSHOT
        |--------------------------------------------------------------------------
        */
        $data = [];

        foreach ($times as $time) {
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
                $data[] = [
                    'time' => $time->measured_at,
                    'gardu' => $g->nama,
                    'batteries' => ($batteryRows->get($g->id) ?? collect())->values(),
                    'chargers' => ($chargerRows->get($g->id) ?? collect())->values(),
                ];
            }
        }

        if ($request->filled('from') || $request->filled('to')) {
            $times->appends($request->query());
        }

        return view('page.table', compact(
            'resor',
            'gardu',
            'data',
            'times',
            'maxBatt',
            'maxCharger'
        ));
    }
}
