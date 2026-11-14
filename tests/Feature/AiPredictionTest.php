<?php

namespace Tests\Feature;

use App\Models\BatteryDaily;
use App\Models\Gardu;
use App\Models\IrForecast;
use App\Models\IrForecastRun;
use App\Services\IrForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class AiPredictionTest extends TestCase
{
    use RefreshDatabase;

    private function seedGardu(): void
    {
        DB::table('daop')->insert(['id' => 1, 'nama' => 'Test', 'wilayah' => 'Test']);
        foreach ([1, 2] as $id) {
            DB::table('resor')->insert([
                'id' => $id, 'nama' => 'Resor '.$id, 'alamat' => 'Test', 'n_asset' => 1, 'daop_id' => 1,
            ]);
        }
        DB::table('gardu')->insert([
            ['id' => 1, 'kode' => 'CJT', 'nama' => 'Cilejit', 'n_bank' => 1, 'n_batt' => 2, 'address' => 'Test', 'latitude' => 0, 'longitude' => 0, 'resor_id' => 1],
            ['id' => 2, 'kode' => 'LBK', 'nama' => 'Lebak', 'n_bank' => 1, 'n_batt' => 2, 'address' => 'Test', 'latitude' => 0, 'longitude' => 0, 'resor_id' => 2],
        ]);
        foreach ([1, 2] as $id) {
            DB::table('battery')->insert([
                'id' => $id, 'serial_no' => 'B'.$id, 'merk' => 'Test',
                'kapasitas' => 100, 'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
            ]);
        }
        DB::table('charger')->insert([
            'id' => 1, 'serial_no' => 'C1', 'merk' => 'Test', 'kapasitas' => 100,
            'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
        ]);
    }

    private function monitoring(int $id, int $batteryId, string $at, float $thd, float $volt = 13.6, float $temp = 24.0): void
    {
        DB::table('battery_monitoring')->insert([
            'id' => $id, 'measured_at' => $at, 'volt' => $volt, 'thd' => $thd, 'temp' => $temp,
            'soc' => 100, 'soh' => 90, 'battery_id' => $batteryId,
        ]);
    }

    private function daily(int $batteryId, string $date, float $ir): void
    {
        DB::table('battery_daily')->insert([
            'battery_id' => $batteryId, 'gardu_id' => 1, 'date' => $date, 'ir' => $ir,
            'volt' => 13.6, 'temp' => 24, 'v_charger' => 122.7, 'i_mean' => 0, 'i_min' => 0, 'samples' => 48,
        ]);
    }

    private function forecastRow(int $batteryId, string $quality = 'ok', array $extra = []): array
    {
        return array_merge([
            'battery_id' => $batteryId, 'horizon' => 1, 'model' => 'LSTM_h1',
            'anchor_date' => '2026-05-02', 'target_date' => '2026-05-03',
            'r_anchor' => 4.5, 'r_pred' => 4.52, 'r_low' => 4.49, 'r_high' => 4.55,
            'seed_std' => 0.001, 'flat_days' => 0, 'max_z' => 2.1, 'quality' => $quality,
        ], $extra);
    }

    private function fakeModel(array $forecasts, array $skipped = []): void
    {
        Process::fake(['*' => Process::result(json_encode(compact('forecasts', 'skipped')))]);
    }

    private function asAdmin(): static
    {
        return $this->withSession(['auth_user' => ['role' => 'admin', 'username' => 'admin']]);
    }

    public function test_daily_aggregate_matches_thesis_rules_and_skips_running_day(): void
    {
        $this->seedGardu();
        $this->monitoring(1, 1, '2026-05-01 08:00:00', 4.10, 13.0, 20.0);
        $this->monitoring(2, 1, '2026-05-01 23:30:00', 4.20, 14.0, 22.0);
        $this->monitoring(3, 1, '2026-05-02 01:00:00', 9.99);
        DB::table('charger_monitoring')->insert([
            ['id' => 1, 'measured_at' => '2026-05-01 08:00:00', 'voltage' => 122, 'current' => 1, 'charger_id' => 1],
            ['id' => 2, 'measured_at' => '2026-05-01 09:00:00', 'voltage' => 124, 'current' => -3, 'charger_id' => 1],
        ]);

        app(IrForecastService::class)->aggregateDaily();

        $this->assertSame(1, BatteryDaily::count());
        $row = BatteryDaily::first();
        $this->assertSame('2026-05-01', $row->date->toDateString());
        $this->assertEquals(4.20, (float) $row->ir);
        $this->assertEquals(13.5, (float) $row->volt);
        $this->assertEquals(21.0, (float) $row->temp);
        $this->assertEquals(123.0, (float) $row->v_charger);
        $this->assertEquals(-1.0, (float) $row->i_mean);
        $this->assertEquals(-3.0, (float) $row->i_min);
    }

    public function test_forecast_stores_results_and_skips_processed_anchor(): void
    {
        $this->seedGardu();
        $this->daily(1, '2026-05-02', 4.5);
        $this->fakeModel([$this->forecastRow(1)], [
            ['battery_id' => 1, 'horizon' => 30, 'model' => 'LSTM_h30', 'required' => 61, 'available' => 1, 'reason' => 'insufficient_history'],
        ]);

        $service = app(IrForecastService::class);
        $gardu = Gardu::find(1);
        $run = $service->forecastGardu($gardu);

        $this->assertSame('partial', $run->status);
        $this->assertSame(1, $run->n_forecast);
        $this->assertSame(1, IrForecast::count());
        $this->assertNull($service->forecastGardu($gardu));
        $this->assertSame(1, IrForecastRun::count());
        Process::assertRanTimes(fn ($process) => true, 1);
    }

    public function test_model_failure_is_recorded_and_retried(): void
    {
        $this->seedGardu();
        $this->daily(1, '2026-05-02', 4.5);
        Process::fake(['*' => Process::result('', 'ModuleNotFoundError: keras', 1)]);

        $service = app(IrForecastService::class);
        $run = $service->forecastGardu(Gardu::find(1));

        $this->assertSame('failed', $run->status);
        $this->assertStringContainsString('keras', $run->error);

        $service->forecastGardu(Gardu::find(1));
        $this->assertSame(2, IrForecastRun::count());
    }

    public function test_page_renders_forecast_and_warns_about_frozen_ir(): void
    {
        $this->seedGardu();
        $this->daily(1, '2026-05-02', 4.5);
        $this->fakeModel([$this->forecastRow(1, 'ir_frozen', ['flat_days' => 21])]);
        app(IrForecastService::class)->forecastGardu(Gardu::find(1));

        $this->asAdmin()->get('/ai-prediction')
            ->assertOk()
            ->assertSee('Prediksi tidak dapat dipercaya')
            ->assertSee('Tidak andal')
            ->assertSee('4.520');
    }

    public function test_page_renders_gardu_without_forecast_as_unvalidated(): void
    {
        $this->seedGardu();

        $this->asAdmin()->get('/ai-prediction?gardu_id=2')
            ->assertOk()
            ->assertSee('Belum divalidasi')
            ->assertSee('Belum ada prediksi');
    }

    public function test_model_receives_gardu_anchor_for_every_cell(): void
    {
        $this->seedGardu();
        $this->daily(1, '2026-05-02', 4.5);
        $this->daily(2, '2026-04-20', 4.1);
        $this->fakeModel([$this->forecastRow(1)]);

        app(IrForecastService::class)->forecastGardu(Gardu::find(1));

        Process::assertRan(function ($process) {
            $cells = collect(json_decode($process->input, true)['cells']);

            return $cells->count() === 2 && $cells->every(fn ($cell) => $cell['anchor'] === '2026-05-02');
        });
    }

    public function test_page_shows_backtest_reliability_of_unvalidated_gardu(): void
    {
        $this->seedGardu();
        $path = tempnam(sys_get_temp_dir(), 'backtest');
        file_put_contents($path, "gardu,h,skill_pct\nLBK,1,-59.1\nLBK,7,8.0\n");
        config(['ai.backtest' => $path]);

        $this->asAdmin()->get('/ai-prediction?gardu_id=2')
            ->assertOk()
            ->assertSee('kalah dari persistence pada H+1');

        unlink($path);
    }

    public function test_resor_route_cannot_select_gardu_of_other_resor(): void
    {
        $this->seedGardu();

        $this->asAdmin()->get('/ai-prediction/resor/2?gardu_id=1')
            ->assertOk()
            ->assertViewHas('selectedGardu', fn ($gardu) => $gardu->id === 2);
    }

    public function test_pending_forecast_fills_every_missed_day(): void
    {
        $this->seedGardu();
        foreach (['2026-05-01', '2026-05-02', '2026-05-03', '2026-05-04'] as $date) {
            $this->daily(1, $date, 4.5);
        }
        IrForecastRun::create(['gardu_id' => 1, 'anchor_date' => '2026-05-02', 'status' => 'ok']);
        IrForecastRun::create(['gardu_id' => 1, 'anchor_date' => '2026-05-03', 'status' => 'failed']);
        $this->fakeModel([]);

        $runs = app(IrForecastService::class)->forecastPending(Gardu::find(1), 30);

        $this->assertSame(['2026-05-01', '2026-05-03', '2026-05-04'], $runs->map(fn ($run) => $run->anchor_date->toDateString())->all());
        Process::assertRan(fn ($process) => collect(json_decode($process->input, true)['cells'])->every(fn ($cell) => $cell['anchor'] === '2026-05-01'));
        $this->assertSame([], app(IrForecastService::class)->forecastPending(Gardu::find(1), 30)->all());
    }

    public function test_pending_forecast_respects_catchup_window(): void
    {
        $this->seedGardu();
        foreach (['2026-04-01', '2026-05-03', '2026-05-04'] as $date) {
            $this->daily(1, $date, 4.5);
        }
        $this->fakeModel([]);

        $runs = app(IrForecastService::class)->forecastPending(Gardu::find(1), 2);

        $this->assertSame(['2026-05-03', '2026-05-04'], $runs->map(fn ($run) => $run->anchor_date->toDateString())->all());
    }

    public function test_tabulation_compares_prediction_with_actual(): void
    {
        $this->seedGardu();
        $this->daily(1, '2026-05-03', 4.60);
        DB::table('ir_forecast')->insert(['gardu_id' => 1] + $this->forecastRow(1));
        DB::table('ir_forecast')->insert(['gardu_id' => 1] + $this->forecastRow(1, 'ok', ['horizon' => 7, 'model' => 'LSTM_h7', 'target_date' => '2026-05-09']));

        $this->asAdmin()->get('/ai-prediction/tabulation?gardu_id=1&from=2026-05-01&to=2026-05-10')
            ->assertOk()
            ->assertSee('4.5200')
            ->assertSee('4.600')
            ->assertSee('-0.0800')
            ->assertSee('1.74')
            ->assertSee('Menunggu nilai aktual')
            ->assertViewHas('metrics', fn ($m) => $m[1]['n'] === 1 && abs($m[1]['mae'] - 0.08) < 1e-6 && $m[7]['n'] === 0)
            ->assertViewHas('pending', 1);

        $this->asAdmin()->get('/ai-prediction/tabulation?gardu_id=1&from=2026-05-01&to=2026-05-10&horizon=7')
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
    }

    public function test_tabulation_exports_csv(): void
    {
        $this->seedGardu();
        $this->daily(1, '2026-05-03', 4.60);
        DB::table('ir_forecast')->insert(['gardu_id' => 1] + $this->forecastRow(1));

        $response = $this->asAdmin()->get('/ai-prediction/tabulation?gardu_id=1&from=2026-05-01&to=2026-05-10&export=1');

        $response->assertOk()->assertDownload('tabulasi-prediksi-ir-cilejit-20260501_20260510.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('IR Prediksi (mOhm)', $csv);
        $this->assertStringContainsString('03/05/2026,H+1,"Sel 1",LSTM_h1,4.5,4.52,4.6,-0.08,1.74,ok', $csv);
        $this->assertStringNotContainsString('Rentang', $csv);
    }

    public function test_forecast_page_uses_latest_anchor_in_date_range(): void
    {
        $this->seedGardu();
        DB::table('ir_forecast')->insert(['gardu_id' => 1] + $this->forecastRow(1));
        DB::table('ir_forecast')->insert(['gardu_id' => 1] + $this->forecastRow(1, 'ok', [
            'anchor_date' => '2026-05-10', 'target_date' => '2026-05-11', 'r_pred' => 4.61,
        ]));

        $this->asAdmin()->get('/ai-prediction?gardu_id=1')
            ->assertOk()
            ->assertViewHas('selectedAnchor', '2026-05-10')
            ->assertViewHas('fromDate', '2026-03-10')
            ->assertSee('4.610')
            ->assertDontSee('Rentang 95%')
            ->assertViewHas('chart', fn ($chart) => in_array(4.52, $chart['forecast'][1], true) && in_array(4.61, $chart['forecast'][1], true));

        $this->asAdmin()->get('/ai-prediction?gardu_id=1&from=2026-05-01&to=2026-05-05')
            ->assertViewHas('selectedAnchor', '2026-05-02')
            ->assertSee('4.520');
    }

    public function test_forecast_page_limits_range_to_two_months(): void
    {
        $this->seedGardu();

        $this->asAdmin()->get('/ai-prediction?gardu_id=1&from=2026-01-01&to=2026-05-31')
            ->assertOk()
            ->assertViewHas('fromDate', '2026-03-31')
            ->assertSee('Rentang tanggal maksimal 62 hari');
    }

    public function test_status_endpoint_reports_latest_run_version(): void
    {
        $this->seedGardu();
        $this->monitoring(1, 1, '2026-05-02 08:00:00', 4.25);
        $run = IrForecastRun::create(['gardu_id' => 1, 'anchor_date' => '2026-05-01', 'status' => 'ok']);

        $this->asAdmin()->get('/ai-prediction/status?gardu_id=1')
            ->assertOk()
            ->assertJsonPath('version', $run->id)
            ->assertJsonPath('latest.ir', 4.25);
    }
}
