<?php

namespace App\Http\Controllers;
use App\Models\Gardu;
use Illuminate\Support\Facades\DB; 

class DashboardController extends Controller
{
    public function dashboard()
    {
        // Indexed lookup with a stable tie-breaker for equal measurement times.
        $latestMonitoringId = DB::table('battery_monitoring')
            ->select('id')
            ->whereColumn('battery_id', 'battery.id')
            ->orderByDesc('measured_at')
            ->orderByDesc('id')
            ->limit(1);

        $gardu = Gardu::select(
                'gardu.id',
                'gardu.kode',
                'gardu.nama',
                'gardu.address',
                'gardu.latitude',
                'gardu.longitude',
                'gardu.n_batt',
                DB::raw('ROUND(AVG(lm.soh), 2) as avg_soh')
            )
            ->leftJoin('battery', 'battery.gardu_id', '=', 'gardu.id')
            ->leftJoin('battery_monitoring as lm', function ($join) use ($latestMonitoringId) {
                $join->where('lm.id', '=', $latestMonitoringId);
            })
            ->groupBy(
                'gardu.id',
                'gardu.kode',
                'gardu.nama',
                'gardu.address',
                'gardu.latitude',
                'gardu.longitude',
                'gardu.n_batt'
            )
            ->get();

        return view('dashboard', compact('gardu'));
    }
}
