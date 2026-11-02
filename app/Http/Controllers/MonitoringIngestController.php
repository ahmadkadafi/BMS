<?php

namespace App\Http\Controllers;

use App\Services\LoggerSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringIngestController extends Controller
{
    /**
     * Terima data monitoring dari Node-RED, simpan, lalu langsung cek anomali
     * terhadap battery_setting/charger_setting sehingga logger terisi tanpa menunggu cron.
     *
     * Body: {"battery": [{battery_id, measured_at?, volt, thd, temp, soc, soh}],
     *        "charger": [{charger_id, measured_at?, voltage, current}]}
     */
    public function store(Request $request, LoggerSyncService $loggerSync): JsonResponse
    {
        $value = 'required|numeric|between:-9999.99,9999.99';
        $data = $request->validate([
            'battery' => 'array|max:500',
            'battery.*.battery_id' => 'required|integer|exists:battery,id',
            'battery.*.measured_at' => 'nullable|date',
            'battery.*.volt' => $value,
            'battery.*.thd' => $value,
            'battery.*.temp' => $value,
            'battery.*.soc' => $value,
            'battery.*.soh' => $value,
            'charger' => 'array|max:500',
            'charger.*.charger_id' => 'required|integer|exists:charger,id',
            'charger.*.measured_at' => 'nullable|date',
            'charger.*.voltage' => $value,
            'charger.*.current' => $value,
        ]);

        $now = now();
        $battery = collect($data['battery'] ?? [])->map(fn (array $row) => [
            'battery_id' => $row['battery_id'],
            'measured_at' => $this->measuredAt($row['measured_at'] ?? null, $now),
            'volt' => $row['volt'],
            'thd' => $row['thd'],
            'temp' => $row['temp'],
            'soc' => $row['soc'],
            'soh' => $row['soh'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $charger = collect($data['charger'] ?? [])->map(fn (array $row) => [
            'charger_id' => $row['charger_id'],
            'measured_at' => $this->measuredAt($row['measured_at'] ?? null, $now),
            'voltage' => $row['voltage'],
            'current' => $row['current'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Insert tanpa observer; pengecekan threshold dilakukan sekali lewat logger sync
        DB::transaction(function () use ($battery, $charger) {
            if ($battery->isNotEmpty()) {
                DB::table('battery_monitoring')->insert($battery->all());
            }
            if ($charger->isNotEmpty()) {
                DB::table('charger_monitoring')->insert($charger->all());
            }
        });

        // Jika sync lain sedang berjalan, data ini tetap diproses oleh sync tersebut atau cron berikutnya
        $synced = $loggerSync->sync(waitSeconds: 5);

        return response()->json([
            'stored' => ['battery' => $battery->count(), 'charger' => $charger->count()],
            'logger_synced' => $synced !== null,
        ], 201);
    }

    private function measuredAt(?string $value, Carbon $now): string
    {
        $timezone = config('app.timezone');

        return ($value ? Carbon::parse($value, $timezone)->setTimezone($timezone) : $now)
            ->format('Y-m-d H:i:s');
    }
}
