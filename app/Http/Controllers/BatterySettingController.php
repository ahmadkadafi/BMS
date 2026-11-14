<?php

namespace App\Http\Controllers;

use App\Models\Resor;
use App\Models\Gardu;
use App\Models\BatterySetting;
use App\Support\ThresholdRules;
use Illuminate\Http\Request;

class BatterySettingController extends Controller
{
    private const PAIRS = [
        ['volt_min_warn', 'volt_min_alarm', 'Voltage min', 'min'],
        ['volt_max_warn', 'volt_max_alarm', 'Voltage max', 'max'],
        ['temp_min_warn', 'temp_min_alarm', 'Temperature min', 'min'],
        ['temp_max_warn', 'temp_max_alarm', 'Temperature max', 'max'],
        ['thd_warn', 'thd_alarm', 'IR', 'max'],
        ['soc_warn', 'soc_alarm', 'SOC', 'min'],
        ['soh_warn', 'soh_alarm', 'SOH', 'min'],
    ];

    private const RANGES = [
        ['volt_min_warn', 'volt_max_warn', 'Voltage warning'],
        ['volt_min_alarm', 'volt_max_alarm', 'Voltage alarm'],
        ['temp_min_warn', 'temp_max_warn', 'Temperature warning'],
        ['temp_min_alarm', 'temp_max_alarm', 'Temperature alarm'],
    ];

    public function index(?Resor $resor = null)
    {
        $gardu = Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->with('batterySetting')
            ->orderBy('id')
            ->get();

        return view('page.batterysetting', compact(
            'resor',
            'gardu'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, ['gardu_id' => 'required|exists:gardu,id']);

        BatterySetting::updateOrCreate(
            ['gardu_id' => $data['gardu_id']],
            $data
        );

        return back()->with('success', 'Battery setting berhasil disimpan');
    }

    public function update(Request $request, $id)
    {
        BatterySetting::findOrFail($id)->update($this->validated($request));

        return back()->with('success', 'Battery setting berhasil diperbarui');
    }

    public function destroy($gardu_id)
    {
        BatterySetting::where('gardu_id', $gardu_id)->delete();

        return redirect()
            ->back()
            ->with('success', 'Battery setting berhasil dihapus.');
    }

    private function validated(Request $request, array $extra = []): array
    {
        $value = 'nullable|numeric|between:-9999.99,9999.99';
        $percent = 'nullable|numeric|between:0,100';

        return ThresholdRules::validate($request, $extra + [
            'volt_min_warn' => $value, 'volt_min_alarm' => $value,
            'volt_max_warn' => $value, 'volt_max_alarm' => $value,
            'temp_min_warn' => $value, 'temp_min_alarm' => $value,
            'temp_max_warn' => $value, 'temp_max_alarm' => $value,
            'thd_warn' => 'nullable|numeric|between:0,9999.99', 'thd_alarm' => 'nullable|numeric|between:0,9999.99',
            'soc_warn' => $percent, 'soc_alarm' => $percent,
            'soh_warn' => $percent, 'soh_alarm' => $percent,
        ], self::PAIRS, self::RANGES);
    }
}
