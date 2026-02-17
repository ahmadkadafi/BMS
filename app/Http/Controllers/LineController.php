<?php

namespace App\Http\Controllers;

use App\Models\Resor;
use Illuminate\Http\Request;

class LineController extends Controller
{
    public function index(Request $request, Resor $resor)
    {
        // Ambil semua gardu dalam resor
        $gardu = $resor->gardu()
            ->with([
                'batteries.monitorings' => function ($q) {
                    // ambil data 7 hari terakhir saja
                    $q->where('measured_at', '>=', now()->subDays(7))
                      ->orderBy('measured_at');
                },
                'chargers.monitorings' => function ($q) {
                    $q->where('measured_at', '>=', now()->subDays(7))
                      ->orderBy('measured_at');
                }
            ])
            ->orderBy('nama')
            ->get();

        return view('page.line', compact('resor', 'gardu'));
    }
}
