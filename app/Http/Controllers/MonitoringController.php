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
            }])
            ->get();

        return view('page.monitoring', compact('resor', 'gardu'));
    }
}