<?php

namespace Tests\Feature;

use App\Models\Logger;
use App\Services\AlarmEtaService;
use App\Support\LinearTrend;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlarmEtaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['alarm.debounce_samples' => 3]);

        DB::table('daop')->insert(['id' => 1, 'nama' => 'Test', 'wilayah' => 'Test']);
        DB::table('resor')->insert(['id' => 1, 'nama' => 'Resor 1', 'alamat' => 'Test', 'n_asset' => 1, 'daop_id' => 1]);
        DB::table('gardu')->insert([
            'id' => 1, 'kode' => 'CJT', 'nama' => 'Gardu 1', 'n_bank' => 1, 'n_batt' => 1,
            'address' => 'Test', 'latitude' => 0, 'longitude' => 0, 'resor_id' => 1,
        ]);
        DB::table('battery')->insert([
            'id' => 1, 'serial_no' => 'B1', 'merk' => 'Test', 'kapasitas' => 100,
            'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
        ]);
        DB::table('battery_setting')->insert([
            'gardu_id' => 1, 'thd_warn' => 5, 'thd_alarm' => 6, 'temp_max_warn' => 35, 'temp_max_alarm' => 40,
        ]);
    }

    private function eta(): AlarmEtaService
    {
        return app(AlarmEtaService::class);
    }

    public function test_linear_trend_projects_threshold_crossing(): void
    {
        // Naik 1 per jam dari 30
        $points = array_map(fn ($h) => [$h * 3600.0, 30.0 + $h], range(0, 5));
        $trend = LinearTrend::fit($points);

        $this->assertEqualsWithDelta(1 / 3600, $trend->slope, 1e-9);
        $this->assertEqualsWithDelta(1.0, $trend->r2, 1e-9);
        $this->assertEqualsWithDelta(5 * 3600, $trend->secondsUntil(40, 'max'), 1e-6);
        $this->assertNull($trend->secondsUntil(20, 'min'));
        $this->assertSame(0.0, $trend->secondsUntil(32, 'max'));
    }

    public function test_active_warning_log_gets_eta_to_alarm(): void
    {
        // Suhu naik 0.5 °C tiap 10 menit: 36 → 38, alarm 40 tercapai 40 menit setelah sampel terakhir
        $start = Carbon::parse('2026-11-14 08:00:00');
        foreach (range(0, 4) as $i) {
            DB::table('battery_monitoring')->insert([
                'battery_id' => 1, 'measured_at' => $start->copy()->addMinutes(10 * $i)->format('Y-m-d H:i:s'),
                'volt' => 13.5, 'thd' => 4, 'temp' => 36 + 0.5 * $i, 'soc' => 100, 'soh' => 90,
            ]);
        }
        $log = Logger::create([
            'occurred_at' => $start, 'device_type' => 'battery', 'resor_id' => 1, 'gardu_id' => 1, 'batt_id' => 1,
            'message' => 'Test', 'status' => 'warning', 'alarm_type' => 'warning', 'alarm_code' => 'battery_temp_max',
            'lifecycle_status' => 'open', 'action' => 'coming',
        ]);

        $eta = $this->eta()->forLog($log->fresh());
        $this->assertSame('eta', $eta['state']);
        $this->assertSame('2026-11-14 09:20', $eta['at']->format('Y-m-d H:i'));

        $log->update(['status' => 'alarm']);
        $this->assertSame('reached', $this->eta()->forLog($log->fresh())['state']);

        $log->update(['status' => 'warning', 'cleared_at' => now()]);
        $this->assertNull($this->eta()->forLog($log->fresh()));
    }

    public function test_long_window_is_averaged_per_hour(): void
    {
        $start = Carbon::parse('2026-11-10 00:00:00');
        foreach (range(0, 11) as $hour) {
            foreach ([0, 30] as $minute) {
                DB::table('battery_monitoring')->insert([
                    'battery_id' => 1, 'measured_at' => $start->copy()->addHours($hour)->addMinutes($minute)->format('Y-m-d H:i:s'),
                    'volt' => 13.5, 'thd' => 4 + 0.01 * $hour + ($minute ? 0.002 : 0), 'temp' => 25, 'soc' => 100, 'soh' => 90,
                ]);
            }
        }

        $trend = $this->eta()->trend('battery', 1, 'thd');
        $this->assertSame(12, $trend->n);
        $this->assertGreaterThan(0.99, $trend->r2);
        // Naik 0.01 mΩ per jam dari ±4.11: batas warning 5 sekitar 89 jam lagi
        $this->assertEqualsWithDelta(89, $trend->secondsUntil(5, 'max') / 3600, 1);
    }

    public function test_noisy_or_falling_trend_has_no_eta(): void
    {
        $start = Carbon::parse('2026-11-14 08:00:00');
        foreach ([36, 39, 35, 38, 36, 39] as $i => $temp) {
            DB::table('battery_monitoring')->insert([
                'battery_id' => 1, 'measured_at' => $start->copy()->addMinutes(10 * $i)->format('Y-m-d H:i:s'),
                'volt' => 13.5, 'thd' => 4, 'temp' => $temp, 'soc' => 100, 'soh' => 90,
            ]);
        }

        $trend = $this->eta()->trend('battery', 1, 'temp');
        $this->assertSame('uncertain', $this->eta()->fromTrend($trend, 40, 'max')['state']);

        $falling = LinearTrend::fit([[0, 38], [600, 37], [1200, 36], [1800, 35], [2400, 34]]);
        $this->assertSame('stable', $this->eta()->fromTrend($falling, 40, 'max')['state']);
    }

    public function test_forecast_crossing_interpolates_and_extrapolates(): void
    {
        $anchor = Carbon::parse('2026-05-01');
        $forecasts = collect([
            (object) ['horizon' => 1, 'r_pred' => 4.6],
            (object) ['horizon' => 7, 'r_pred' => 5.2],
            (object) ['horizon' => 30, 'r_pred' => 5.66],
        ]);

        // 4.6 (H+1) → 5.2 (H+7): batas 5 di hari 1 + 0.4/0.6*6 = 5
        $warning = $this->eta()->forecastCrossing($anchor, 4.5, $forecasts, 5);
        $this->assertSame(['eta', 5, false, '2026-05-06'], [$warning['state'], $warning['days'], $warning['extrapolated'], $warning['at']->toDateString()]);

        // Kemiringan H+7→H+30 0.02/hari: batas 6 di hari 30 + 0.34/0.02 = 47
        $alarm = $this->eta()->forecastCrossing($anchor, 4.5, $forecasts, 6);
        $this->assertSame(['eta', 47, true], [$alarm['state'], $alarm['days'], $alarm['extrapolated']]);

        $this->assertSame('reached', $this->eta()->forecastCrossing($anchor, 6.2, $forecasts, 6)['state']);
        $this->assertSame('beyond', $this->eta()->forecastCrossing($anchor, 4.5, $forecasts, 50)['state']);
        $this->assertSame('stable', $this->eta()->forecastCrossing($anchor, 4.5, collect([(object) ['horizon' => 1, 'r_pred' => 4.4]]), 5)['state']);
    }

    public function test_pages_show_eta(): void
    {
        $base = ['gardu_id' => 1, 'battery_id' => 1, 'anchor_date' => '2026-05-01', 'r_anchor' => 4.5,
            'r_low' => 4, 'r_high' => 6, 'seed_std' => 0.001, 'flat_days' => 0, 'max_z' => 1, 'quality' => 'ok'];
        DB::table('ir_forecast')->insert([
            $base + ['horizon' => 1, 'model' => 'LSTM_h1', 'target_date' => '2026-05-02', 'r_pred' => 4.6],
            $base + ['horizon' => 7, 'model' => 'LSTM_h7', 'target_date' => '2026-05-08', 'r_pred' => 5.2],
            $base + ['horizon' => 30, 'model' => 'LSTM_h30', 'target_date' => '2026-05-31', 'r_pred' => 5.66],
        ]);
        $session = ['auth_user' => ['role' => 'admin', 'username' => 'admin']];

        $this->withSession($session)->get('/ai-prediction?gardu_id=1')
            ->assertOk()
            ->assertSee('Warning ± 06/05/2026')
            ->assertSee('Alarm ± 17/06/2026')
            ->assertSee('perkiraan kasar');

        Logger::create([
            'occurred_at' => now(), 'device_type' => 'battery', 'resor_id' => 1, 'gardu_id' => 1, 'batt_id' => 1,
            'message' => 'Test', 'status' => 'warning', 'alarm_type' => 'warning', 'alarm_code' => 'battery_thd_max',
            'lifecycle_status' => 'open', 'action' => 'coming',
        ]);
        $this->withSession($session)->get('/logger/resor/1')
            ->assertOk()
            ->assertSee('Estimasi')
            ->assertSee('Tren belum jelas');
    }
}
