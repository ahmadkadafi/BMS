<?php

use App\Models\BatteryDaily;
use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use App\Models\Gardu;
use App\Observers\BatteryMonitoringObserver;
use App\Observers\ChargerMonitoringObserver;
use App\Services\IrForecastService;
use App\Services\ReportStatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

Artisan::command('logger:sync {--device=all : all, battery, or charger} {--from-start : Process from monitoring ID 1 when sync state is empty} {--batch=100 : Rows processed per batch}', function () {
    $device = $this->option('device');
    $fromStart = (bool) $this->option('from-start');
    $batch = max(1, (int) $this->option('batch'));

    if (! in_array($device, ['all', 'battery', 'charger'], true)) {
        $this->error('Option --device hanya menerima all, battery, atau charger.');

        return self::FAILURE;
    }

    $processedBattery = 0;
    $processedCharger = 0;
    $runSync = function ($modelClass, array $with, $observer, &$processed, string $label, string $source) use ($batch, $fromStart) {
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
                $this->info("{$label} sync state dibuat dari ID terakhir: {$lastId}");
                return;
            }
        } else {
            $lastId = (int) $state->last_id;
        }

        $this->info("Mulai sync {$label} dari ID > {$lastId}");

        $modelClass::query()
            ->with($with)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->chunkById($batch, function ($monitorings) use ($observer, &$processed, $label, $source, &$lastId) {
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

                $this->info("{$label} sync diproses: {$processed}");
            });
    };

    if (in_array($device, ['all', 'battery'], true)) {
        $runSync(
            BatteryMonitoring::class,
            ['battery.gardu.resor', 'battery.gardu.batterySetting'],
            app(BatteryMonitoringObserver::class),
            $processedBattery,
            'battery',
            'battery_monitoring'
        );
    }

    if (in_array($device, ['all', 'charger'], true)) {
        $runSync(
            ChargerMonitoring::class,
            ['charger.gardu.resor', 'charger.chargerSetting'],
            app(ChargerMonitoringObserver::class),
            $processedCharger,
            'charger',
            'charger_monitoring'
        );
    }

    $this->info("Battery monitoring sync: {$processedBattery}");
    $this->info("Charger monitoring sync: {$processedCharger}");

    return self::SUCCESS;
})->purpose('Generate logger warning/alarm dari data monitoring terbaru untuk production.');

Artisan::command('ai:forecast {--gardu= : Kode gardu, kosong berarti semua} {--as-of= : Replay dari tanggal YYYY-MM-DD} {--force : Ulangi meski anchor sudah diproses}', function () {
    $service = app(IrForecastService::class);

    if (! $this->option('as-of')) {
        $this->info('Agregasi harian: '.$service->aggregateDaily().' baris diperbarui.');
    }

    $gardu = Gardu::query()
        ->when($this->option('gardu'), fn ($q, $kode) => $q->where('kode', $kode))
        ->orderBy('id')
        ->get();

    if ($gardu->isEmpty()) {
        $this->error('Gardu tidak ditemukan.');

        return self::FAILURE;
    }

    $failed = false;
    foreach ($gardu as $item) {
        $run = $service->forecastGardu($item, $this->option('as-of'), (bool) $this->option('force'));

        if (! $run) {
            $this->line("{$item->kode}: tidak ada anchor baru");
            continue;
        }

        $failed = $failed || $run->status === 'failed';
        $this->line("{$item->kode}: {$run->status}, anchor {$run->anchor_date->toDateString()}, {$run->n_forecast} prediksi");
    }

    return $failed ? self::FAILURE : self::SUCCESS;
})->purpose('Perbarui data harian lalu jalankan forecast internal resistance.');

Artisan::command('ai:import-history {file : Path daily_long.csv hasil tahap 2} {--gardu=CJT : Kode gardu pemilik data}', function () {
    $gardu = Gardu::query()->where('kode', $this->option('gardu'))->first();
    $handle = @fopen($this->argument('file'), 'r');

    if (! $gardu || ! $handle) {
        $this->error('Gardu atau file tidak ditemukan.');

        return self::FAILURE;
    }

    $batteryIds = $gardu->batteries()->orderBy('id')->pluck('id')->values();
    $header = fgetcsv($handle);
    $rows = [];
    $unmapped = [];
    $now = now();

    while (($line = fgetcsv($handle)) !== false) {
        $row = array_combine($header, $line);
        $batteryId = $batteryIds[(int) $row['cell_id'] - 1] ?? null;

        if (! $batteryId) {
            $unmapped[$row['cell_id']] = true;
            continue;
        }

        $rows[] = [
            'battery_id' => $batteryId,
            'gardu_id' => $gardu->id,
            'date' => $row['date'],
            'ir' => $row['R'],
            'volt' => $row['V'],
            'temp' => $row['T'],
            'v_charger' => $row['V_charger'] !== '' ? $row['V_charger'] : null,
            'i_mean' => $row['I_mean'] !== '' ? $row['I_mean'] : null,
            'i_min' => $row['I_min'] !== '' ? $row['I_min'] : null,
            'samples' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    fclose($handle);

    foreach (array_chunk($rows, 500) as $chunk) {
        BatteryDaily::upsert($chunk, ['battery_id', 'date'], ['ir', 'volt', 'temp', 'v_charger', 'i_mean', 'i_min', 'updated_at']);
    }

    $this->info(count($rows).' baris riwayat diimpor ke '.$gardu->nama.'.');

    if ($unmapped) {
        $this->warn('Sel tanpa data baterai dilewati: '.implode(', ', array_keys($unmapped)));
    }

    return self::SUCCESS;
})->purpose('Impor riwayat harian tesis untuk replay forecast.');

Artisan::command('report:rollup {--from= : Tanggal awal YYYY-MM-DD, default 3 hari lalu} {--to= : Tanggal akhir YYYY-MM-DD, default kemarin}', function () {
    $timezone = config('app.timezone');
    $day = $this->option('from')
        ? Carbon::parse($this->option('from'), $timezone)
        : now($timezone)->subDays(3)->startOfDay();
    $to = $this->option('to')
        ? Carbon::parse($this->option('to'), $timezone)
        : now($timezone)->subDay()->startOfDay();
    $service = app(ReportStatsService::class);
    $processed = 0;

    for (; $day->lte($to); $day->addDay()) {
        $service->rollup($day->copy());
        $processed++;
    }

    $this->info("Ringkasan report diperbarui untuk {$processed} hari.");

    return self::SUCCESS;
})->purpose('Perbarui ringkasan harian untuk page report.');

Schedule::command('logger:sync --batch=200')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('report:rollup')
    ->dailyAt('00:15')
    ->withoutOverlapping();

Schedule::command('ai:forecast')
    ->hourly()
    ->withoutOverlapping();
