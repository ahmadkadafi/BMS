<?php

namespace App\Http\Controllers;

use App\Models\Charger;
use App\Models\ChargerSetting;
use App\Models\Resor;
use App\Support\ThresholdRules;
use Illuminate\Http\Request;

class ChargerSettingController extends Controller
{
    public function index(?Resor $resor = null)
    {
        $chargers = Charger::query()
            ->when($resor, fn ($q) =>
                $q->whereHas('gardu', fn ($gq) => $gq->where('resor_id', $resor->id))
            )
            ->with(['gardu:id,nama', 'chargerSetting'])
            ->orderBy('id')
            ->get();

        return view('page.chargersetting', compact('resor', 'chargers'));
    }

    public function store(Request $request)
    {
        $value = 'nullable|numeric|between:0,9999.99';
        $data = ThresholdRules::validate($request, [
            'charger_id' => 'required|exists:charger,id',
            'float' => $value,
            'boost' => $value,
            'warn_min_volt' => $value,
            'warn_max_volt' => $value,
            'alarm_min_volt' => $value,
            'alarm_max_volt' => $value,
            'warn_curr' => $value,
            'alarm_curr' => $value,
        ], [
            ['warn_min_volt', 'alarm_min_volt', 'Voltage min', 'min'],
            ['warn_max_volt', 'alarm_max_volt', 'Voltage max', 'max'],
            ['warn_curr', 'alarm_curr', 'Current', 'max'],
        ], [
            ['warn_min_volt', 'warn_max_volt', 'Voltage warning'],
            ['alarm_min_volt', 'alarm_max_volt', 'Voltage alarm'],
        ]);

        ChargerSetting::updateOrCreate(
            ['charger_id' => $data['charger_id']],
            $data
        );

        return back()->with('success', 'Charger setting berhasil disimpan');
    }

    public function destroy($chargerId)
    {
        ChargerSetting::where('charger_id', $chargerId)->delete();

        return back()->with('success', 'Charger setting berhasil dihapus');
    }
}
