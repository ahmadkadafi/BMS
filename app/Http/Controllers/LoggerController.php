<?php

namespace App\Http\Controllers;

use App\Models\Logger;
use App\Models\Resor;
use App\Models\Gardu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoggerController extends Controller
{
    public function index(Request $request, Resor $resor)
    {
        $garduOptions = Gardu::query()
            ->where('resor_id', $resor->id)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $logs = Logger::query()
            ->where('resor_id', $resor->id)
            ->whereIn('status', ['warning', 'alarm'])
            ->when($request->filled('gardu_id'), fn ($q) => $q->where('gardu_id', $request->gardu_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->to))
            ->with([
                'resor:id,nama',
                'gardu:id,nama',
                'battery:id,serial_no',
            ])
            ->orderByDesc('occurred_at')
            ->paginate(15)
            ->withQueryString();

        return view('page.logger', [
            'resor' => $resor,
            'garduOptions' => $garduOptions,
            'logs' => $logs,
        ]);
    }

    public function submit(Request $request, Resor $resor, Logger $logger): RedirectResponse
    {
        if ((int) $logger->resor_id !== (int) $resor->id) {
            abort(404);
        }

        if ($logger->action === 'coming') {
            $logger->update(['action' => 'handled']);
        }

        return back()->with('success', 'Notifikasi berhasil disubmit.');
    }
}
