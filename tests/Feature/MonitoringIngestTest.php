<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringIngestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ingest.token' => 'secret-token']);

        DB::table('daop')->insert(['id' => 1, 'nama' => 'Test', 'wilayah' => 'Test']);
        DB::table('resor')->insert(['id' => 1, 'nama' => 'Resor 1', 'alamat' => 'Test', 'n_asset' => 1, 'daop_id' => 1]);
        DB::table('gardu')->insert([
            'id' => 1, 'kode' => 'G1', 'nama' => 'Gardu 1', 'n_bank' => 1, 'n_batt' => 1,
            'address' => 'Test', 'latitude' => 0, 'longitude' => 0, 'resor_id' => 1,
        ]);
        DB::table('battery')->insert([
            'id' => 1, 'serial_no' => 'B1', 'merk' => 'Test', 'kapasitas' => 100,
            'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
        ]);
        DB::table('charger')->insert([
            'id' => 1, 'serial_no' => 'C1', 'merk' => 'Test', 'kapasitas' => 100,
            'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
        ]);
        DB::table('battery_setting')->insert([
            'gardu_id' => 1,
            'volt_min_warn' => 12.0, 'volt_min_alarm' => 11.5, 'volt_max_warn' => 14.0, 'volt_max_alarm' => 14.5,
            'temp_min_warn' => 10, 'temp_min_alarm' => 5, 'temp_max_warn' => 35, 'temp_max_alarm' => 40,
            'thd_warn' => 8, 'thd_alarm' => 10, 'soc_warn' => 50, 'soc_alarm' => 30, 'soh_warn' => 80, 'soh_alarm' => 70,
        ]);
        DB::table('charger_setting')->insert([
            'charger_id' => 1, 'float' => 122, 'boost' => 128,
            'warn_min_volt' => 110, 'alarm_min_volt' => 105, 'warn_max_volt' => 130, 'alarm_max_volt' => 135,
            'warn_curr' => 10, 'alarm_curr' => 15,
        ]);
        // Kondisi production: cron sudah pernah membuat sync state
        DB::table('logger_sync_state')->insert([
            ['source' => 'battery_monitoring', 'last_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['source' => 'charger_monitoring', 'last_id' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_anomaly_is_logged_immediately_and_only_once(): void
    {
        $payload = [
            'battery' => [
                ['battery_id' => 1, 'measured_at' => '2026-11-02 20:00:00', 'volt' => 11.0, 'thd' => 5, 'temp' => 25, 'soc' => 100, 'soh' => 90],
            ],
            'charger' => [
                ['charger_id' => 1, 'measured_at' => '2026-11-02T13:00:00Z', 'voltage' => 120, 'current' => 12],
            ],
        ];

        $this->withToken('secret-token')->postJson('/api/ingest/monitoring', $payload)
            ->assertCreated()
            ->assertJson(['stored' => ['battery' => 1, 'charger' => 1], 'logger_synced' => true]);

        $logs = DB::table('logger')->orderBy('id')->get();
        $this->assertSame(['battery_volt_min', 'charger_current_max'], $logs->pluck('alarm_code')->all());
        $this->assertSame(['alarm', 'warning'], $logs->pluck('status')->all());
        // Waktu UTC dari Node-RED disimpan dalam timezone aplikasi
        $this->assertSame('2026-11-02 20:00:00', DB::table('charger_monitoring')->value('measured_at'));

        // Cron berikutnya tidak memproses ulang baris yang sama
        $this->artisan('logger:sync')->assertSuccessful();
        $this->assertSame(2, DB::table('logger')->count());
        $this->assertSame(1, (int) DB::table('logger_sync_state')->where('source', 'battery_monitoring')->value('last_id'));
    }

    public function test_ir_anomaly_message_uses_milliohm(): void
    {
        $this->withToken('secret-token')->postJson('/api/ingest/monitoring', [
            'battery' => [['battery_id' => 1, 'volt' => 13.2, 'thd' => 9, 'temp' => 25, 'soc' => 100, 'soh' => 90]],
        ])->assertCreated();

        $this->assertSame('IR 9.00 mΩ di atas batas WARNING 8.00 mΩ', DB::table('logger')->value('message'));
    }

    public function test_normal_values_create_no_logger(): void
    {
        $this->withToken('secret-token')->postJson('/api/ingest/monitoring', [
            'battery' => [['battery_id' => 1, 'volt' => 13.2, 'thd' => 5, 'temp' => 25, 'soc' => 100, 'soh' => 90]],
        ])->assertCreated();

        $this->assertSame(1, DB::table('battery_monitoring')->count());
        $this->assertSame(0, DB::table('logger')->count());
    }

    public function test_rejects_bad_token_and_invalid_payload(): void
    {
        $this->post('/api/ingest/monitoring', ['battery' => []])->assertUnauthorized();
        $this->withToken('wrong')->postJson('/api/ingest/monitoring', [])->assertUnauthorized();

        $this->withToken('secret-token')->post('/api/ingest/monitoring', [
            'battery' => [['battery_id' => 99, 'volt' => 'abc']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['battery.0.battery_id', 'battery.0.volt', 'battery.0.soh']);

        config(['services.ingest.token' => null]);
        $this->withToken('')->postJson('/api/ingest/monitoring', [])->assertStatus(503);

        $this->assertSame(0, DB::table('battery_monitoring')->count());
    }
}
