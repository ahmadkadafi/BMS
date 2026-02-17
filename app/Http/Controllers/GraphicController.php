<?php

namespace App\Http\Controllers;

use App\Models\Resor;
use App\Models\Gardu;
use Illuminate\Http\Request;

class GraphicController extends Controller
{
    public function index(Request $request)
    {
        // ===============================
        // RESOR (dari URL / session / default)
        // ===============================
        // asumsi resor dipilih dari sidebar
        $resorId = $request->route('resor') ?? 1;

        $resor = Resor::findOrFail($resorId);

        // ===============================
        // AMBIL GARDU + BATTERY + MONITORING TERAKHIR
        // ===============================
        $gardu = Gardu::with([
            'batteries.monitorings' => function ($q) {
                $q->latest('measured_at');
            },
            'chargers.monitorings' => function ($q) {
                $q->latest('measured_at')->limit(1);
            }
        ])
        ->where('resor_id', $resor->id)
        ->orderBy('nama')
        ->get();

        return view('page.graphic', compact(
            'resor',
            'gardu'
        ));
    }
}
