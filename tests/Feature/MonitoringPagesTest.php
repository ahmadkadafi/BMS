<?php

namespace Tests\Feature;

use App\Http\Controllers\GraphicController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\TableController;
use App\Services\LatestMonitoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-10 12:00:00', config('app.timezone')));

        DB::table('daop')->insert(['id' => 1, 'nama' => 'Test', 'wilayah' => 'Test']);
        DB::table('resor')->insert(['id' => 1, 'nama' => 'Resor 1', 'alamat' => 'Test', 'n_asset' => 1, 'daop_id' => 1]);
        foreach ([1, 2] as $id) {
            DB::table('gardu')->insert([
                'id' => $id, 'kode' => 'G'.$id, 'nama' => 'Gardu '.$id,
                'n_bank' => 1, 'n_batt' => 2, 'address' => 'Test',
                'latitude' => 0, 'longitude' => 0, 'resor_id' => 1,
            ]);
        }
        foreach ([[1, 1], [2, 1], [3, 2]] as [$id, $garduId]) {
            DB::table('battery')->insert([
                'id' => $id, 'serial_no' => 'B'.$id, 'merk' => 'Test',
                'kapasitas' => 100, 'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => $garduId,
            ]);
        }
        DB::table('charger')->insert([
            'id' => 1, 'serial_no' => 'C1', 'merk' => 'Test',
            'kapasitas' => 100, 'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
        ]);
        foreach ([[1, '2026-09-01 10:00:00', 12.0], [1, '2026-09-09 10:00:00', 13.0], [1, '2026-09-09 10:00:00', 13.5],
            [2, '2026-09-08 10:00:00', 11.0], [3, '2026-09-09 10:00:00', 10.0]] as [$battery, $time, $volt]) {
            DB::table('battery_monitoring')->insert([
                'battery_id' => $battery, 'measured_at' => $time,
                'volt' => $volt, 'thd' => 0, 'temp' => 25, 'soc' => 100, 'soh' => 90,
            ]);
        }
        foreach ([['2026-09-08 10:00:00', 54.0], ['2026-09-09 10:00:00', 55.0]] as [$time, $voltage]) {
            DB::table('charger_monitoring')->insert(['charger_id' => 1, 'measured_at' => $time, 'voltage' => $voltage, 'current' => 2]);
        }
    }

    public function test_latest_monitoring_uses_latest_time_then_id(): void
    {
        $request = Request::create('/monitoring', 'GET', ['gardu_id' => 1]);
        $data = app(MonitoringController::class)->index($request, app(LatestMonitoringService::class))->getData();

        $this->assertCount(2, $data['gardu']);
        $this->assertSame([1], $data['filteredGardu']->pluck('id')->all());
        $gardu = $data['filteredGardu']->first();
        $this->assertEquals([13.5, 11.0], $gardu->batteries->map(fn ($b) => (float) $b->monitorings->first()->volt)->all());
        $this->assertEquals(55.0, $gardu->chargers->first()->monitorings->first()->voltage);

        $graphic = app(GraphicController::class)->index(Request::create('/graphic'), app(LatestMonitoringService::class))->getData();
        $this->assertCount(2, $graphic['filteredGardu']);
        $this->assertEquals(10.0, $graphic['filteredGardu']->firstWhere('id', 2)->batteries->first()->monitorings->first()->volt);

        $this->get('/monitoring')->assertRedirect();
    }

    public function test_pages_render_for_admin(): void
    {
        $this->withSession(['auth_user' => ['role' => 'admin']]);

        $this->get('/monitoring?gardu_id=1')->assertOk()->assertSee('Volt Charge 55 V', false);
        $this->get('/graphic')->assertOk()->assertSee('voltChart2', false);
        $this->get('/line?gardu_id=1&from=2026-08-01&to=2026-09-09')
            ->assertOk()
            ->assertSee('Rentang tanggal maksimal 30 hari. Filter disesuaikan', false)
            ->assertSee('max="2026-09-10"', false);
        $this->get('/table?gardu_id=1&table=thd&from=2026-09-01&to=2026-09-09')
            ->assertOk()
            ->assertSee('Batt 2 (mΩ)', false)
            ->assertSee('3 data', false);
    }

    public function test_line_filter_uses_selected_range_and_aligns_series(): void
    {
        $data = $this->line(['gardu_id' => 1, 'chart' => 'volt', 'from' => '2026-09-08', 'to' => '2026-09-09']);

        $this->assertNull($data['rangeNotice']);
        $this->assertSame(['08/09 10:00', '09/09 10:00'], $data['chartData']['labels']);
        $this->assertSame([null, 13.25], $data['chartData']['datasets'][0]['data']);
        $this->assertSame([11.0, null], $data['chartData']['datasets'][1]['data']);

        $charger = $this->line(['gardu_id' => 1, 'chart' => 'voltage_charger', 'from' => '2026-09-09', 'to' => '2026-09-09']);
        $this->assertSame([[55.0]], array_column($charger['chartData']['datasets'], 'data'));
    }

    public function test_line_range_is_clamped_to_thirty_days_and_input_is_sanitized(): void
    {
        $data = $this->line(['from' => '2026-08-01', 'to' => '2026-09-09', 'chart' => 'volt; DROP TABLE battery']);

        $this->assertSame('2026-08-11', $data['fromDate']);
        $this->assertSame('2026-09-09', $data['toDate']);
        $this->assertNotNull($data['rangeNotice']);
        $this->assertSame('volt', $data['selectedChart']);
        // Rentang 30 hari dirata-rata per 3 jam
        $this->assertSame(['01/09 09:00', '08/09 09:00', '09/09 09:00'], $data['chartData']['labels']);

        $swapped = $this->line(['from' => '2026-09-09', 'to' => '2026-09-03']);
        $this->assertSame(['2026-09-03', '2026-09-09'], [$swapped['fromDate'], $swapped['toDate']]);
        $this->assertNull($swapped['rangeNotice']);

        $invalid = $this->line(['from' => 'abc', 'to' => '2026-02-31']);
        $this->assertSame(['2026-09-10', '2026-09-10'], [$invalid['fromDate'], $invalid['toDate']]);
        $this->assertSame([], $invalid['chartData']['datasets']);
    }

    public function test_table_keeps_battery_columns_aligned_and_supports_all_gardu(): void
    {
        $data = $this->table(['gardu_id' => 1, 'table' => 'volt', 'from' => '2026-09-01', 'to' => '2026-09-09']);
        $rows = $data['rows'];

        $this->assertSame(['Batt 1 (V)', 'Batt 2 (V)'], $data['valueColumns']);
        $this->assertSame(3, $rows->total());
        $this->assertSame(['09/09 10:00', '08/09 10:00', '01/09 10:00'], $rows->getCollection()->map(fn ($r) => $r['time']->format('d/m H:i'))->all());
        // Battery 1 tidak punya data 8 September; nilai battery 2 tetap di kolom kedua
        $this->assertEquals([null, 11.0], $rows->getCollection()[1]['values']);

        $all = $this->table(['gardu_id' => '', 'table' => 'volt', 'from' => '2026-09-01', 'to' => '2026-09-09']);
        $this->assertNull($all['selectedGarduId']);
        $this->assertSame(['Gardu 1', 'Gardu 2', 'Gardu 1', 'Gardu 1'], $all['rows']->getCollection()->pluck('gardu')->all());

        $charger = $this->table(['gardu_id' => 1, 'table' => 'charger', 'from' => '2026-09-08', 'to' => '2026-09-09']);
        $this->assertSame(['Volt Charge (V)', 'Current Charge (A)'], $charger['valueColumns']);
        $this->assertEquals([[55, 2], [54, 2]], $charger['rows']->getCollection()->pluck('values')->all());

        $clamped = $this->table(['table' => 'bogus', 'from' => '2026-07-01', 'to' => '2026-09-09']);
        $this->assertSame(['2026-08-11', '2026-09-09', 'volt'], [$clamped['fromDate'], $clamped['toDate'], $clamped['selectedTable']]);
        $this->assertNotNull($clamped['rangeNotice']);
    }

    public function test_table_csv_export_contains_every_row_in_range(): void
    {
        $this->withSession(['auth_user' => ['role' => 'admin']]);

        $response = $this->get('/table?gardu_id=1&table=volt&from=2026-09-01&to=2026-09-09&export=1');
        $response->assertOk()->assertDownload('bms-gardu-1-volt-20260901_20260909.csv');

        $lines = array_map('str_getcsv', explode("
", trim(substr($response->streamedContent(), 3))));
        $this->assertSame(['No', 'Date', 'Time', 'Lokasi', 'Batt 1 (V)', 'Batt 2 (V)'], $lines[0]);
        $this->assertCount(4, $lines);
        $this->assertSame(['2', '08/09/2026', '10:00:00', 'Gardu 1', '-', '11'], $lines[2]);
    }

    private function table(array $query): array
    {
        return app(TableController::class)->index(Request::create('/table', 'GET', $query))->getData();
    }

    private function line(array $query): array
    {
        return app(LineController::class)->index(Request::create('/line', 'GET', $query))->getData();
    }
}
