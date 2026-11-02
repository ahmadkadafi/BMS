<?php

namespace App\Services;

use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LatestMonitoringService
{
    /**
     * Pasang monitoring terakhir ke setiap battery/charger sebagai relasi `monitorings`
     * (berisi 0 atau 1 item) supaya view lama tetap bisa dipakai.
     * Gardu harus sudah memuat relasi batteries dan chargers.
     */
    public function attach(Collection $gardu): void
    {
        $batteries = $gardu->flatMap->batteries;
        $chargers = $gardu->flatMap->chargers;

        $latestBattery = $this->latest(BatteryMonitoring::class, 'battery', 'battery_monitoring', 'battery_id', $batteries->pluck('id'));
        $latestCharger = $this->latest(ChargerMonitoring::class, 'charger', 'charger_monitoring', 'charger_id', $chargers->pluck('id'));

        $batteries->each(fn ($battery) => $battery->setRelation(
            'monitorings',
            collect(array_filter([$latestBattery->get($battery->id)]))
        ));

        $chargers->each(fn ($charger) => $charger->setRelation(
            'monitorings',
            collect(array_filter([$latestCharger->get($charger->id)]))
        ));
    }

    /**
     * Satu lookup terindeks per device (device_id, measured_at, id), lalu ambil baris lengkapnya sekali.
     */
    private function latest(string $model, string $deviceTable, string $table, string $foreignKey, Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $latestId = DB::table($table)
            ->select('id')
            ->whereColumn($foreignKey, $deviceTable.'.id')
            ->orderByDesc('measured_at')
            ->orderByDesc('id')
            ->limit(1);

        $latestIds = DB::table($deviceTable)
            ->whereIn('id', $ids)
            ->selectSub($latestId, 'latest_id')
            ->pluck('latest_id')
            ->filter();

        if ($latestIds->isEmpty()) {
            return collect();
        }

        return $model::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy($foreignKey);
    }
}
