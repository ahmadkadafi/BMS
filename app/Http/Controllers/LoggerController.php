<?php

namespace App\Http\Controllers;

use App\Models\Logger;
use App\Models\Resor;
use App\Models\Gardu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoggerController extends Controller
{
    public function index(Request $request, ?Resor $resor = null)
    {
        $garduOptions = Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $logs = Logger::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->whereIn('status', ['warning', 'alarm'])
            ->when($request->filled('gardu_id'), fn ($q) => $q->where('gardu_id', $request->gardu_id))
            ->when($request->filled('status'), fn ($q) => $q->where('lifecycle_status', $request->status))
            ->when($request->filled('alarm_type'), fn ($q) => $q->where('alarm_type', $request->alarm_type))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->to))
            ->with([
                'resor:id,nama',
                'gardu:id,nama',
                'battery:id,serial_no',
                'charger:id,merk,serial_no',
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

        $data = $request->validate([
            'action' => ['required', 'in:confirm,close'],
        ]);

        if ($data['action'] === 'confirm' && $logger->lifecycle_status === 'open') {
            $logger->update([
                'lifecycle_status' => 'confirm',
                'confirmed_at' => $logger->confirmed_at ?? now(),
                'action' => 'handled',
            ]);

            return back()->with('success', 'Alarm berhasil dikonfirmasi.');
        }

        if ($data['action'] === 'close' && in_array($logger->lifecycle_status, ['open', 'confirm'], true)) {
            $logger->update([
                'lifecycle_status' => 'closed',
                'confirmed_at' => $logger->confirmed_at ?? now(),
                'closed_at' => now(),
                'action' => 'released',
            ]);

            return back()->with('success', 'Alarm berhasil ditutup.');
        }

        return back()->with('success', 'Status alarm tidak berubah.');
    }
}
