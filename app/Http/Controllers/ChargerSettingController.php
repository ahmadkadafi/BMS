<?php

namespace App\Http\Controllers;

use App\Models\Charger;
use App\Models\ChargerSetting;
use App\Models\Resor;
use Illuminate\Http\Request;

class ChargerSettingController extends Controller
{
    public function index($resorId)
    {
        $resor = Resor::findOrFail($resorId);

        $chargers = Charger::query()
            ->whereHas('gardu', fn ($q) => $q->where('resor_id', $resorId))
            ->with(['gardu:id,nama', 'chargerSetting'])
            ->orderBy('id')
            ->get();

        return view('page.chargersetting', compact('resor', 'chargers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'charger_id' => 'required|exists:charger,id',
            'float' => 'nullable|numeric',
            'boost' => 'nullable|numeric',
            'warn_min_volt' => 'nullable|numeric',
            'warn_max_volt' => 'nullable|numeric',
            'alarm_min_volt' => 'nullable|numeric',
            'alarm_max_volt' => 'nullable|numeric',
            'warn_curr' => 'nullable|numeric',
            'alarm_curr' => 'nullable|numeric',
        ]);

        ChargerSetting::updateOrCreate(
            ['charger_id' => $request->charger_id],
            $request->only([
                'charger_id',
                'float',
                'boost',
                'warn_min_volt',
                'warn_max_volt',
                'alarm_min_volt',
                'alarm_max_volt',
                'warn_curr',
                'alarm_curr',
            ])
        );

        return back()->with('success', 'Charger setting berhasil disimpan');
    }

    public function destroy($chargerId)
    {
        ChargerSetting::where('charger_id', $chargerId)->delete();

        return back()->with('success', 'Charger setting berhasil dihapus');
    }
}
