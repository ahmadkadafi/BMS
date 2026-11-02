<?php

namespace App\Services;

use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use App\Observers\BatteryMonitoringObserver;
use App\Observers\ChargerMonitoringObserver;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoggerSyncService
{
    /**
     * Cek monitoring baru (ID > last_id) terhadap battery_setting/charger_setting dan buat logger.
     * Dipakai oleh cron logger:sync dan endpoint ingest. Lock memastikan setiap baris hanya
     * diproses sekali walau keduanya berjalan bersamaan.
     *
     * Mengembalikan null jika sync lain masih berjalan lebih dari $waitSeconds.
     */
    public function sync(string $device = 'all', bool $fromStart = false, int $batch = 100, int $waitSeconds = 0, ?callable $progress = null): ?array
    {
        $lock = Cache::lock('logger-sync', 600);

        try {
            $waitSeconds > 0 ? $lock->block($waitSeconds) : ($lock->get() ?: throw new LockTimeoutException);
        } catch (LockTimeoutException) {
            return null;
        }

        try {
            $result = ['battery' => 0, 'charger' => 0];
            $progress ??= fn (string $message) => null;

            if (in_array($device, ['all', 'battery'], true)) {
                $result['battery'] = $this->syncSource(
                    BatteryMonitoring::class,
                    ['battery.gardu.resor', 'battery.gardu.batterySetting'],
                    app(BatteryMonitoringObserver::class),
                    'battery',
                    'battery_monitoring',
                    $fromStart,
                    $batch,
                    $progress
                );
            }

            if (in_array($device, ['all', 'charger'], true)) {
                $result['charger'] = $this->syncSource(
                    ChargerMonitoring::class,
                    ['charger.gardu.resor', 'charger.chargerSetting'],
                    app(ChargerMonitoringObserver::class),
                    'charger',
                    'charger_monitoring',
                    $fromStart,
                    $batch,
                    $progress
                );
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function syncSource(string $modelClass, array $with, $observer, string $label, string $source, bool $fromStart, int $batch, callable $progress): int
    {
        $processed = 0;
        $state = DB::table('logger_sync_state')->where('source', $source)->first();

        if (! $state) {
            $lastId = $fromStart ? 0 : (int) $modelClass::query()->max('id');

            DB::table('logger_sync_state')->insert([
                'source' => $source,
                'last_id' => $lastId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! $fromStart) {
                $progress("{$label} sync state dibuat dari ID terakhir: {$lastId}");

                return 0;
            }
        } else {
            $lastId = (int) $state->last_id;
        }

        $progress("Mulai sync {$label} dari ID > {$lastId}");

        $modelClass::query()
            ->with($with)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->chunkById($batch, function ($monitorings) use ($observer, &$processed, $label, $source, &$lastId, $progress) {
                foreach ($monitorings as $monitoring) {
                    $observer->created($monitoring);
                    $processed++;
                    $lastId = max($lastId, (int) $monitoring->id);
                }

                DB::table('logger_sync_state')
                    ->where('source', $source)
                    ->update([
                        'last_id' => $lastId,
                        'updated_at' => now(),
                    ]);

                $progress("{$label} sync diproses: {$processed}");
            });

        return $processed;
    }
}
