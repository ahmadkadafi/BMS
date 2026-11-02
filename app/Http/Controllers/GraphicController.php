<?php

namespace App\Http\Controllers;

use App\Models\Resor;
use App\Models\Gardu;
use App\Services\LatestMonitoringService;
use Illuminate\Http\Request;

class GraphicController extends Controller
{
    public function index(Request $request, LatestMonitoringService $latestMonitoring, ?Resor $resor = null)
    {
        $gardu = Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->orderBy('nama')
            ->get(['id', 'nama']);

        // Data monitoring hanya dimuat untuk gardu yang ditampilkan
        $filteredGardu = $request->filled('gardu_id')
            ? $gardu->where('id', (int) $request->gardu_id)->values()
            : $gardu;

        $filteredGardu->load([
            'batteries' => fn ($q) => $q->select('id', 'gardu_id')->orderBy('id'),
            'chargers' => fn ($q) => $q->select('id', 'gardu_id')->orderBy('id'),
        ]);
        $latestMonitoring->attach($filteredGardu);

        return view('page.graphic', compact(
            'resor',
            'gardu',
            'filteredGardu'
        ));
    }
}
