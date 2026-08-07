<?php

namespace App\Http\Controllers;

use App\Models\Resor;
use App\Models\Gardu;
use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GraphicController extends Controller
{
    public function index(Request $request, Resor $resor)
    {
        $gardu = Gardu::where('resor_id', $resor->id)
            ->with(['batteries:id,gardu_id', 'chargers:id,gardu_id'])
            ->orderBy('nama')
            ->get();

        $batteryIds = $gardu->flatMap->batteries->pluck('id');
        $chargerIds = $gardu->flatMap->chargers->pluck('id');

        $latestBatteryMonitorings = collect();
        if ($batteryIds->isNotEmpty()) {
            $latestBatteryTimes = BatteryMonitoring::query()
                ->select('battery_id', DB::raw('MAX(measured_at) as latest_time'))
                ->whereIn('battery_id', $batteryIds)
                ->groupBy('battery_id');

            $latestBatteryMonitorings = BatteryMonitoring::query()
                ->joinSub($latestBatteryTimes, 'latest_battery', function ($join) {
                    $join->on('battery_monitoring.battery_id', '=', 'latest_battery.battery_id')
                        ->on('battery_monitoring.measured_at', '=', 'latest_battery.latest_time');
                })
                ->select('battery_monitoring.*')
                ->get()
                ->keyBy('battery_id');
        }

        $latestChargerMonitorings = collect();
        if ($chargerIds->isNotEmpty()) {
            $latestChargerTimes = ChargerMonitoring::query()
                ->select('charger_id', DB::raw('MAX(measured_at) as latest_time'))
                ->whereIn('charger_id', $chargerIds)
                ->groupBy('charger_id');

            $latestChargerMonitorings = ChargerMonitoring::query()
                ->joinSub($latestChargerTimes, 'latest_charger', function ($join) {
                    $join->on('charger_monitoring.charger_id', '=', 'latest_charger.charger_id')
                        ->on('charger_monitoring.measured_at', '=', 'latest_charger.latest_time');
                })
                ->select('charger_monitoring.*')
                ->get()
                ->keyBy('charger_id');
        }

        $gardu->each(function ($g) use ($latestBatteryMonitorings, $latestChargerMonitorings) {
            $g->batteries->each(function ($battery) use ($latestBatteryMonitorings) {
                $monitoring = $latestBatteryMonitorings->get($battery->id);
                $battery->setRelation('monitorings', $monitoring ? collect([$monitoring]) : collect());
            });

            $g->chargers->each(function ($charger) use ($latestChargerMonitorings) {
                $monitoring = $latestChargerMonitorings->get($charger->id);
                $charger->setRelation('monitorings', $monitoring ? collect([$monitoring]) : collect());
            });
        });

        return view('page.graphic', compact(
            'resor',
            'gardu'
        ));
    }
}
