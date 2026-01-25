<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resor;
use App\Models\Gardu;
use App\Models\BatteryMonitoring;

class TableController extends Controller
{
    public function index(Request $request, Resor $resor)
    {
        $gardu = Gardu::where('resor_id', $resor->id)->get();

        /*
        |--------------------------------------------------------------------------
        | SNAPSHOT TIME (PAGINATION)
        |--------------------------------------------------------------------------
        */
        $times = BatteryMonitoring::select('measured_at')
            ->whereHas('battery.gardu', function ($q) use ($resor, $request) {
                $q->where('resor_id', $resor->id);

                if ($request->filled('gardu_id')) {
                    $q->where('id', $request->gardu_id);
                }
            })
            ->when($request->filled('from'), fn ($q) =>
                $q->whereDate('measured_at', '>=', $request->from)
            )
            ->when($request->filled('to'), fn ($q) =>
                $q->whereDate('measured_at', '<=', $request->to)
            )
            ->orderByDesc('measured_at')
            ->distinct()
            ->paginate(5)
            ->withQueryString(); // 5 snapshot per halaman

        /*
        |--------------------------------------------------------------------------
        | DATA PER SNAPSHOT
        |--------------------------------------------------------------------------
        */
        $data = [];

        foreach ($times as $time) {
            $rows = BatteryMonitoring::with(['battery.gardu'])
                ->where('measured_at', $time->measured_at)
                ->whereHas('battery.gardu', fn ($q) =>
                    $q->where('resor_id', $resor->id)
                )
                ->when($request->filled('gardu_id'), fn ($q) =>
                    $q->whereHas('battery.gardu', fn ($qq) =>
                        $qq->where('id', $request->gardu_id)
                    )
                )
                ->orderBy('battery_id')
                ->get();

            $data[] = [
                'time' => $time->measured_at,
                'gardu' => optional($rows->first()?->battery?->gardu)->nama,
                'rows' => $rows
            ];
        }

        return view('page.table', compact(
            'resor',
            'gardu',
            'data',
            'times'
        ));
    }
}
