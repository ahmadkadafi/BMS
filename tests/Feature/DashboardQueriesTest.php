<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResorDashboardController;
use App\Models\Resor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class DashboardQueriesTest extends TestCase
{
    use RefreshDatabase;

    private function seedLocations(): void
    {
        DB::table('daop')->insert(['id' => 1, 'nama' => 'Test', 'wilayah' => 'Test']);
        foreach ([1, 2] as $id) {
            DB::table('resor')->insert([
                'id' => $id, 'nama' => 'Resor '.$id, 'alamat' => 'Test', 'n_asset' => 1, 'daop_id' => 1,
            ]);
            DB::table('gardu')->insert([
                'id' => $id, 'kode' => 'G'.$id, 'nama' => 'Gardu '.$id,
                'n_bank' => 1, 'n_batt' => 3, 'address' => 'Test',
                'latitude' => 0, 'longitude' => 0, 'resor_id' => $id,
            ]);
        }
    }

    public function test_dashboard_uses_latest_time_then_id_and_preserves_empty_gardu(): void
    {
        $this->seedLocations();
        foreach ([1, 2, 3] as $id) {
            DB::table('battery')->insert([
                'id' => $id, 'serial_no' => 'B'.$id, 'merk' => 'Test',
                'kapasitas' => 100, 'pemasangan' => '2026-01-01', 'status' => 'active', 'gardu_id' => 1,
            ]);
        }
        foreach ([[1, '2026-09-01', 10], [1, '2026-09-02', 60], [1, '2026-09-02', 80],
            [1, '2026-08-01', 20], [2, '2026-09-01', 100]] as [$battery, $date, $soh]) {
            DB::table('battery_monitoring')->insert([
                'battery_id' => $battery, 'measured_at' => $date.' 00:00:00',
                'volt' => 12, 'thd' => 0, 'temp' => 25, 'soc' => 100, 'soh' => $soh,
            ]);
        }

        $gardu = app(DashboardController::class)->dashboard()->getData()['gardu']->keyBy('id');
        $this->assertCount(2, $gardu);
        $this->assertEquals(90, $gardu[1]->avg_soh);
        $this->assertNull($gardu[2]->avg_soh);

        $scoped = app(ResorDashboardController::class)->index(Resor::findOrFail(1))->getData()['gardu'];
        $this->assertSame([1], $scoped->pluck('id')->all());
        $this->assertEquals(90, $scoped->first()->avg_soh);
    }

    public function test_notification_counts_preserve_status_action_and_resor_filters(): void
    {
        $this->seedLocations();
        foreach ([[1, 'warning', 'coming'], [1, 'alarm', 'coming'], [2, 'alarm', 'coming'],
            [1, 'warning', 'handled'], [1, 'info', 'coming']] as [$resor, $status, $action]) {
            DB::table('logger')->insert([
                'occurred_at' => '2026-09-01 00:00:00', 'device_type' => 'other',
                'resor_id' => $resor, 'message' => 'Test', 'status' => $status, 'action' => $action,
            ]);
        }
        foreach ([['admin', null, 1, 2], ['user', 1, 1, 1], ['user', 2, 0, 1]] as [$role, $resor, $warnings, $alarms]) {
            session(['auth_user' => ['role' => $role, 'allowed_resor_id' => $resor]]);
            $view = View::make('partials.header');
            View::callComposer($view);
            $data = $view->getData();
            $this->assertSame($warnings, $data['warningCount']);
            $this->assertSame($alarms, $data['alarmCount']);
            $this->assertSame($warnings + $alarms, $data['notificationCount']);
            $this->assertCount($warnings + $alarms, $data['latestNotifications']);
        }
    }
}
