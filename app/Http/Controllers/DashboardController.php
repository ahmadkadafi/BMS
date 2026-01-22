<?php

namespace App\Http\Controllers;
use App\Models\Gardu;
use Illuminate\Support\Facades\DB; 

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // subquery: data battery_monitoring terakhir per battery
        $latestMonitoring = DB::table('battery_monitoring as bm1')
            ->select(
                'bm1.battery_id',
                'bm1.soh'
            )
            ->join(
                DB::raw('
                    (
                        SELECT battery_id, MAX(measured_at) AS latest_time
                        FROM battery_monitoring
                        GROUP BY battery_id
                    ) bm2
                '),
                function ($join) {
                    $join->on('bm1.battery_id', '=', 'bm2.battery_id')
                         ->on('bm1.measured_at', '=', 'bm2.latest_time');
                }
            );
        
        // query utama: avg soh per gardu
        $gardu = Gardu::select(
                'gardu.id',
                'gardu.kode',
                'gardu.nama',
                'gardu.address',
                'gardu.latitude',
                'gardu.longitude',
                'gardu.n_batt',
                DB::raw('ROUND(AVG(lm.soh), 1) as avg_soh')
            )
            ->leftJoin('battery', 'battery.gardu_id', '=', 'gardu.id')
            ->leftJoinSub($latestMonitoring, 'lm', function ($join) {
                $join->on('lm.battery_id', '=', 'battery.id');
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
