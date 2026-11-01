<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 5, 20, 12, 0, 0, config('app.timezone')));

        DB::table('daop')->insert(['id' => 1, 'nama' => 'Test', 'wilayah' => 'Test']);
        DB::table('resor')->insert(['id' => 1, 'nama' => 'Resor', 'alamat' => 'Test', 'n_asset' => 1, 'daop_id' => 1]);
        DB::table('gardu')->insert([
            ['id' => 1, 'kode' => 'A', 'nama' => 'Gardu A', 'n_bank' => 1, 'n_batt' => 1, 'address' => 'Test', 'latitude' => 0, 'longitude' => 0, 'resor_id' => 1],
            ['id' => 2, 'kode' => 'B', 'nama' => 'Gardu B', 'n_bank' => 1, 'n_batt' => 1, 'address' => 'Test', 'latitude' => 0, 'longitude' => 0, 'resor_id' => 1],
        ]);
        foreach ([1, 2] as $id) {
            DB::table('battery')->insert([
                'id' => $id, 'serial_no' => 'B'.$id, 'merk' => 'Test', 'kapasitas' => 100,
                'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => $id,
            ]);
        }
        DB::table('charger')->insert([
            'id' => 1, 'serial_no' => 'C1', 'merk' => 'Test', 'kapasitas' => 100,
            'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function battery(int $batteryId, string $at, float $temp): void
    {
        DB::table('battery_monitoring')->insert([
            'measured_at' => $at, 'volt' => 13.6, 'thd' => 4, 'temp' => $temp,
            'soc' => 100, 'soh' => 90, 'battery_id' => $batteryId,
        ]);
    }

    private function cards(): array
    {
        $response = $this->withSession(['auth_user' => ['role' => 'admin', 'username' => 'admin']])
            ->get('/report/all?month=5&year=2026');
        $response->assertOk();

        return $response->viewData('reportCards')->keyBy(fn ($card) => $card['gardu']->kode)->all();
    }

    public function test_extremes_keep_earliest_min_and_latest_max_on_ties(): void
    {
        $this->battery(1, '2026-05-02 08:00:00', 20);
        $this->battery(1, '2026-05-03 08:00:00', 30);
        $this->battery(1, '2026-05-04 08:00:00', 20);
        $this->battery(1, '2026-05-05 08:00:00', 30);

        $card = $this->cards()['A'];

        $this->assertEquals(20, $card['min_temp']);
        $this->assertSame('2026-05-02', $card['min_temp_at']);
        $this->assertEquals(30, $card['max_temp']);
        $this->assertSame('2026-05-05', $card['max_temp_at']);
    }

    public function test_closed_days_are_stored_and_today_stays_live(): void
    {
        $this->battery(1, '2026-05-19 08:00:00', 25);
        $this->battery(1, '2026-05-20 08:00:00', 26);

        $this->cards();

        $this->assertSame(1, DB::table('monitoring_daily_stats')->count());
        $this->assertSame(1, DB::table('monitoring_daily_stats')->whereDate('date', '2026-05-19')->count());

        $this->battery(1, '2026-05-20 11:00:00', 40);
        $this->assertEquals(40, $this->cards()['A']['max_temp']);
    }

    public function test_page_reads_closed_days_without_scanning_monitoring_again(): void
    {
        $this->battery(1, '2026-05-02 08:00:00', 20);
        $this->battery(1, '2026-05-03 08:00:00', 21);
        $this->cards();

        DB::enableQueryLog();
        $this->cards();
        $grouped = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'group by "b"."gardu_id"'));

        $this->assertCount(0, $grouped);
    }

    public function test_charger_only_and_empty_metrics_stay_separate_per_gardu(): void
    {
        $this->battery(2, '2026-05-02 08:00:00', 24);
        DB::table('charger_monitoring')->insert([
            'measured_at' => '2026-05-02 08:00:00', 'voltage' => 122.5, 'current' => -1.5, 'charger_id' => 1,
        ]);

        $cards = $this->cards();

        $this->assertNull($cards['A']['min_temp']);
        $this->assertEquals(122.5, $cards['A']['max_voltage_charge']);
        $this->assertEquals(-1.5, $cards['A']['min_current_charge']);
        $this->assertEquals(24, $cards['B']['max_temp']);
        $this->assertNull($cards['B']['max_voltage_charge']);
    }

    public function test_alert_counts_are_limited_to_selected_month(): void
    {
        foreach ([['2026-05-31 23:00:00', 'warning'], ['2026-05-01 00:00:00', 'alarm'], ['2026-06-01 00:00:00', 'alarm']] as $i => [$at, $status]) {
            DB::table('logger')->insert([
                'occurred_at' => $at, 'device_type' => 'battery', 'daop_id' => 1, 'resor_id' => 1, 'gardu_id' => 1,
                'message' => 'Test '.$i, 'status' => $status, 'alarm_type' => $status, 'action' => 'coming',
            ]);
        }

        $card = $this->cards()['A'];

        $this->assertSame(1, $card['warning_count']);
        $this->assertSame(1, $card['alarm_count']);
    }
}
