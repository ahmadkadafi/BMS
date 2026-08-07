<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resor;
use App\Models\Gardu;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index(Resor $resor)
    {
        // Ambil semua gardu di resor
        $gardu = Gardu::where('resor_id', $resor->id)
            ->with(['batteries' => function ($q) {
                $q->with(['monitorings' => function ($qm) {
                    $qm->latest('measured_at')->limit(1);
                }]);
            }, 'chargers' => function ($q) {
                $q->with(['monitorings' => function ($qm) {
                    $qm->latest('measured_at')->limit(1);
                }]);
            }])
            ->get();

        $dummyMonitoring = $gardu->mapWithKeys(function ($g) {
            $base = ($g->id % 5) * 5;
            $plnOffline = $g->id % 4 === 0;
            $microwaveIssue = $g->id % 3 === 0;

            return [
                $g->id => [
                    'battery' => [
                        'level' => min(100, 72 + $base),
                        'health' => min(100, 72 + $base) >= 85 ? 'Baik' : (min(100, 72 + $base) >= 65 ? 'Waspada' : 'Kritis'),
                        'voltage' => 48 + ($base / 10),
                        'temp' => 25 + ($g->id % 6),
                        'soc' => 65 + ($g->id % 30),
                        'soh' => 70 + ($g->id % 28),
                    ],
                    'pln' => [
                        'status' => $plnOffline ? 'OFFLINE' : 'ONLINE',
                        'voltage' => $plnOffline ? 0 : 220,
                        'frequency' => $plnOffline ? 0 : 50,
                        'load' => number_format(2.8 + ($g->id % 5) * 0.7, 1),
                        'relay' => $plnOffline ? 'Auto-switch' : 'Normal',
                    ],
                    'microwave' => [
                        'link' => $microwaveIssue ? 'Gangguan' : 'Tersambung',
                        'latency' => $microwaveIssue ? 28 : 12 + ($g->id % 8),
                        'quality' => $microwaveIssue ? '65%' : '86%',
                        'tx' => $microwaveIssue ? -32 : -18,
                        'rx' => $microwaveIssue ? -35 : -22,
                        'signal' => $microwaveIssue ? '-88' : '-68',
                    ],
                    'baseStation' => [
                        'status' => $g->id % 4 === 2 ? 'Gangguan' : 'Aktif',
                        'traffic' => number_format(18 + ($g->id % 6) * 4.5, 1),
                        'rfPower' => -46 + ($g->id % 5),
                        'signal' => -70 + ($g->id % 8),
                        'maintenance' => $g->id % 5 === 0 ? 'Terjadwal' : 'Normal',
                    ],
                ],
            ];
        })->toArray();

        return view('page.monitoring', compact('resor', 'gardu', 'dummyMonitoring'));
    }
}
