<?php

use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use App\Observers\BatteryMonitoringObserver;
use App\Observers\ChargerMonitoringObserver;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('logger:backfill {--device=all : all, battery, or charger} {--from= : Start date YYYY-MM-DD} {--to= : End date YYYY-MM-DD} {--from-id= : Start monitoring ID} {--to-id= : End monitoring ID} {--limit= : Maximum rows per device} {--batch=100 : Rows processed per batch}', function () {
    $device = $this->option('device');
    $from = $this->option('from');
    $to = $this->option('to');
    $fromId = $this->option('from-id') ? (int) $this->option('from-id') : null;
    $toId = $this->option('to-id') ? (int) $this->option('to-id') : null;
    $limit = $this->option('limit') ? (int) $this->option('limit') : null;
    $batch = max(1, (int) $this->option('batch'));

    if (! in_array($device, ['all', 'battery', 'charger'], true)) {
        $this->error('Option --device hanya menerima all, battery, atau charger.');

        return self::FAILURE;
    }

    if (! is_null($limit) && $limit < 1) {
        $this->error('Option --limit harus lebih besar dari 0.');

        return self::FAILURE;
    }

    if ((! is_null($fromId) && $fromId < 1) || (! is_null($toId) && $toId < 1)) {
        $this->error('Option --from-id dan --to-id harus lebih besar dari 0.');

        return self::FAILURE;
    }

    $processedBattery = 0;
    $processedCharger = 0;
    $runBackfill = function ($query, $observer, &$processed, string $label) use ($limit, $batch) {
        $this->info("Mulai proses {$label}...");

        if ($limit && $limit <= $batch) {
            $query->limit($limit)->get()->each(function ($monitoring) use ($observer, &$processed) {
                $observer->created($monitoring);
                $processed++;
            });

            $this->info("{$label} diproses: {$processed}");

            return;
        }

        $query->chunkById($batch, function ($monitorings) use ($observer, &$processed, $limit, $label) {
            foreach ($monitorings as $monitoring) {
                if ($limit && $processed >= $limit) {
                    return false;
                }

                $observer->created($monitoring);
                $processed++;
            }

            $this->info("{$label} diproses: {$processed}");

            return ! $limit || $processed < $limit;
        });
    };

    if (in_array($device, ['all', 'battery'], true)) {
        $observer = app(BatteryMonitoringObserver::class);

        $query = BatteryMonitoring::query()
            ->with(['battery.gardu.resor', 'battery.gardu.batterySetting'])
            ->when($from, fn ($q) => $q->whereDate('measured_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('measured_at', '<=', $to))
            ->when($fromId, fn ($q) => $q->where('id', '>=', $fromId))
            ->when($toId, fn ($q) => $q->where('id', '<=', $toId))
            ->orderBy('id');

        $runBackfill($query, $observer, $processedBattery, 'battery');
    }

    if (in_array($device, ['all', 'charger'], true)) {
        $observer = app(ChargerMonitoringObserver::class);

        $query = ChargerMonitoring::query()
            ->with(['charger.gardu.resor', 'charger.chargerSetting'])
            ->when($from, fn ($q) => $q->whereDate('measured_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('measured_at', '<=', $to))
            ->when($fromId, fn ($q) => $q->where('id', '>=', $fromId))
            ->when($toId, fn ($q) => $q->where('id', '<=', $toId))
            ->orderBy('id');

        $runBackfill($query, $observer, $processedCharger, 'charger');
    }

    $this->info("Battery monitoring diproses: {$processedBattery}");
    $this->info("Charger monitoring diproses: {$processedCharger}");
    $this->info('Logger dibuat jika data melewati parameter warning/alarm dan belum pernah tercatat.');

    return self::SUCCESS;
})->purpose('Generate logger warning/alarm dari data monitoring yang sudah ada.');

Artisan::command('logger:sync {--device=all : all, battery, or charger} {--minutes=10 : Process monitoring rows from the last N minutes} {--batch=100 : Rows processed per batch}', function () {
    $device = $this->option('device');
    $minutes = max(1, (int) $this->option('minutes'));
    $batch = max(1, (int) $this->option('batch'));
    $from = now()->subMinutes($minutes);

    if (! in_array($device, ['all', 'battery', 'charger'], true)) {
        $this->error('Option --device hanya menerima all, battery, atau charger.');

        return self::FAILURE;
    }

    $processedBattery = 0;
    $processedCharger = 0;
    $runSync = function ($query, $observer, &$processed, string $label) use ($batch) {
        $query->chunkById($batch, function ($monitorings) use ($observer, &$processed, $label) {
            foreach ($monitorings as $monitoring) {
                $observer->created($monitoring);
                $processed++;
            }

            $this->info("{$label} sync diproses: {$processed}");
        });
    };

    if (in_array($device, ['all', 'battery'], true)) {
        $query = BatteryMonitoring::query()
            ->with(['battery.gardu.resor', 'battery.gardu.batterySetting'])
            ->where('measured_at', '>=', $from)
            ->orderBy('id');

        $runSync($query, app(BatteryMonitoringObserver::class), $processedBattery, 'battery');
    }

    if (in_array($device, ['all', 'charger'], true)) {
        $query = ChargerMonitoring::query()
            ->with(['charger.gardu.resor', 'charger.chargerSetting'])
            ->where('measured_at', '>=', $from)
            ->orderBy('id');

        $runSync($query, app(ChargerMonitoringObserver::class), $processedCharger, 'charger');
    }

    $this->info("Battery monitoring sync: {$processedBattery}");
    $this->info("Charger monitoring sync: {$processedCharger}");

    return self::SUCCESS;
})->purpose('Generate logger warning/alarm dari data monitoring terbaru untuk production.');

Schedule::command('logger:sync --minutes=10 --batch=200')
    ->everyMinute()
    ->withoutOverlapping();
