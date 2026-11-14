<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlarmLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private int $minute = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ingest.token' => 'secret-token',
            'alarm.debounce_samples' => 3,
            'alarm.hysteresis_pct' => 2,
        ]);

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
        DB::table('battery_setting')->insert(['gardu_id' => 1, 'thd_warn' => 8, 'thd_alarm' => 10]);
        DB::table('logger_sync_state')->insert([
            ['source' => 'battery_monitoring', 'last_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['source' => 'charger_monitoring', 'last_id' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function ir(float ...$values): void
    {
        foreach ($values as $value) {
            $this->battery(['thd' => $value]);
        }
    }

    private function battery(array $values): void
    {
        $this->withToken('secret-token')->postJson('/api/ingest/monitoring', [
            'battery' => [$values + [
                'battery_id' => 1,
                'measured_at' => $this->time($this->minute++),
                'volt' => 13.2, 'thd' => 5, 'temp' => 25, 'soc' => 100, 'soh' => 90,
            ]],
        ])->assertCreated();
    }

    private function time(int $minute): string
    {
        return now()->setDate(2026, 11, 14)->setTime(8, 0)->addMinutes($minute)->format('Y-m-d H:i:s');
    }

    public function test_log_needs_consecutive_violations_and_starts_at_first_one(): void
    {
        $this->ir(9, 9, 5, 9, 9);
        $this->assertSame(0, DB::table('logger')->count());

        $this->ir(9);
        $log = DB::table('logger')->sole();
        $this->assertSame('warning', $log->status);
        $this->assertSame($this->time(3), $log->occurred_at);
        $this->assertNull($log->cleared_at);
    }

    public function test_escalation_is_debounced_sticky_and_tracks_peak(): void
    {
        $this->ir(9, 9, 9, 11, 12);
        $this->assertSame('warning', DB::table('logger')->value('status'));

        $this->ir(11);
        $log = DB::table('logger')->sole();
        $this->assertSame('alarm', $log->status);
        $this->assertSame($this->time(5), $log->escalated_at);
        $this->assertEquals(12, $log->peak_value);

        // Turun ke level warning: status tetap alarm, pesan mengikuti nilai saat ini
        $this->ir(9);
        $log = DB::table('logger')->sole();
        $this->assertSame('alarm', $log->status);
        $this->assertSame('IR 9.00 mΩ di atas batas WARNING 8.00 mΩ', $log->message);
        $this->assertEquals(9, $log->last_value);
    }

    public function test_recovery_uses_hysteresis_and_next_incident_creates_new_log(): void
    {
        $this->ir(9, 9, 9);

        // 7.9 di bawah batas 8 tetapi masih di dalam margin 2% (7.84): belum pulih
        $this->ir(7.9, 7.9, 7.9);
        $this->assertNull(DB::table('logger')->value('cleared_at'));

        $this->ir(7.5, 7.5);
        $this->assertNull(DB::table('logger')->value('cleared_at'));
        $this->ir(7.5);
        $this->assertSame($this->time(6), DB::table('logger')->value('cleared_at'));

        $this->ir(9, 9, 9);
        $this->assertSame(2, DB::table('logger')->count());
        $this->assertNull(DB::table('logger')->orderByDesc('id')->value('cleared_at'));
    }

    public function test_operator_close_while_active_does_not_duplicate_log(): void
    {
        $this->ir(9, 9, 9);
        DB::table('logger')->update(['lifecycle_status' => 'closed', 'action' => 'released', 'closed_at' => now()]);

        $this->ir(9, 9, 9);
        $this->assertSame(1, DB::table('logger')->count());
    }

    public function test_sensor_fault_is_logged_instead_of_threshold_alarm(): void
    {
        DB::table('battery_setting')->update(['volt_min_warn' => 12, 'volt_min_alarm' => 11.5]);

        $this->battery(['volt' => 0]);
        $this->battery(['volt' => 0]);
        $this->assertSame(0, DB::table('logger')->count());

        $this->battery(['volt' => 0]);
        $log = DB::table('logger')->sole();
        $this->assertSame('battery_volt_fault', $log->alarm_code);
        $this->assertSame('sensor_fault', $log->alarm_type);
        $this->assertSame($this->time(0), $log->occurred_at);
        $this->assertSame('Sensor Voltage 0.00 V di luar rentang wajar 1 s/d 20 V, data diabaikan', $log->message);

        $this->battery([]);
        $this->battery([]);
        $this->assertNull(DB::table('logger')->value('cleared_at'));
        $this->battery([]);
        $this->assertSame($this->time(3), DB::table('logger')->value('cleared_at'));
    }

    public function test_faulty_sample_breaks_debounce_and_never_clears_alarm(): void
    {
        // Satu sampel IR 0 di tengah: streak pelanggaran terputus, tidak ada log fault
        $this->ir(9, 9, 0, 9, 9);
        $this->assertSame(0, DB::table('logger')->count());
        $this->ir(9);
        $this->assertSame($this->time(3), DB::table('logger')->value('occurred_at'));

        // IR 0 lolos dari batas max, tetapi tidak boleh dianggap pulih
        $this->ir(0, 0, 0);
        $logs = DB::table('logger')->orderBy('id')->get()->keyBy('alarm_code');
        $this->assertNull($logs['battery_thd_max']->cleared_at);
        $this->assertNull($logs['battery_thd_fault']->cleared_at);

        $this->ir(5, 5, 5);
        $this->assertSame(0, DB::table('logger')->whereNull('cleared_at')->count());
    }

    public function test_fault_check_runs_without_setting(): void
    {
        DB::table('battery_setting')->delete();

        $this->battery(['soc' => 150]);
        $this->battery(['soc' => 150]);
        $this->battery(['soc' => 150]);

        $this->assertSame(['battery_soc_fault'], DB::table('logger')->pluck('alarm_code')->all());
    }

    public function test_daily_aggregate_ignores_faulty_samples(): void
    {
        DB::table('battery_monitoring')->insert([
            ['battery_id' => 1, 'measured_at' => '2026-05-01 08:00:00', 'volt' => 13.0, 'thd' => 4.1, 'temp' => 20, 'soc' => 100, 'soh' => 90],
            ['battery_id' => 1, 'measured_at' => '2026-05-01 09:00:00', 'volt' => 0, 'thd' => 4.3, 'temp' => 20, 'soc' => 100, 'soh' => 90],
            ['battery_id' => 1, 'measured_at' => '2026-05-01 10:00:00', 'volt' => 14.0, 'thd' => 0, 'temp' => 22, 'soc' => 100, 'soh' => 90],
            ['battery_id' => 1, 'measured_at' => '2026-05-01 11:00:00', 'volt' => 14.0, 'thd' => 4.2, 'temp' => 22, 'soc' => 100, 'soh' => 90],
            // Hari berikutnya agar 1 Mei dianggap lengkap
            ['battery_id' => 1, 'measured_at' => '2026-05-02 01:00:00', 'volt' => 14.0, 'thd' => 4.2, 'temp' => 22, 'soc' => 100, 'soh' => 90],
        ]);
        DB::table('charger_monitoring')->insert([
            ['charger_id' => 1, 'measured_at' => '2026-05-01 08:00:00', 'voltage' => 122, 'current' => 1],
            ['charger_id' => 1, 'measured_at' => '2026-05-01 09:00:00', 'voltage' => 0, 'current' => 0],
        ]);

        app(\App\Services\IrForecastService::class)->aggregateDaily();

        $row = DB::table('battery_daily')->sole();
        $this->assertEquals(4.2, $row->ir);
        $this->assertEquals(13.5, $row->volt);
        $this->assertEquals(2, $row->samples);
        $this->assertEquals(122, $row->v_charger);
    }

    public function test_logger_and_setting_pages_show_lifecycle(): void
    {
        $this->ir(9, 9, 9, 11, 11, 11, 5, 5, 5, 9, 9, 9);
        $session = ['auth_user' => ['role' => 'admin', 'username' => 'admin']];

        $this->withSession($session)->get('/logger/resor/1')
            ->assertOk()
            ->assertSee('PULIH')
            ->assertSee('AKTIF')
            ->assertSee('Puncak 11')
            ->assertSee('berlangsung');

        $this->withSession($session)->get('/batterysetting/resor/1')->assertOk();
        $this->withSession($session)->get('/chargersetting/resor/1')->assertOk();
    }

    public function test_battery_setting_rejects_inverted_thresholds(): void
    {
        $session = ['auth_user' => ['role' => 'admin', 'username' => 'admin']];

        $this->withSession($session)->post('/batterysetting/store', [
            'gardu_id' => 1, 'thd_warn' => 10, 'thd_alarm' => 8,
            'soc_warn' => 30, 'soc_alarm' => 50,
            'volt_min_warn' => 14, 'volt_max_warn' => 13,
            'soh_warn' => 120,
        ])->assertSessionHasErrors(['thd_alarm', 'soc_alarm', 'volt_max_warn', 'soh_warn']);
        $this->assertEquals(8, DB::table('battery_setting')->value('thd_warn'));

        $this->withSession($session)->post('/batterysetting/store', [
            'gardu_id' => 1, 'thd_warn' => 9, 'thd_alarm' => 12, 'soc_warn' => 50, 'soc_alarm' => 30,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(12, DB::table('battery_setting')->value('thd_alarm'));
    }

    public function test_charger_setting_rejects_inverted_thresholds(): void
    {
        $this->withSession(['auth_user' => ['role' => 'admin', 'username' => 'admin']])
            ->post('/chargersetting/store', [
                'charger_id' => 1, 'warn_curr' => 15, 'alarm_curr' => 10, 'warn_min_volt' => 100, 'alarm_min_volt' => 105,
            ])->assertSessionHasErrors(['alarm_curr', 'alarm_min_volt']);

        $this->assertSame(0, DB::table('charger_setting')->count());
    }
}
