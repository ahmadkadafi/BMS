<?php

namespace App\Http\Controllers;

use App\Models\Resor;
use App\Models\Gardu;
use App\Models\BatterySetting;
use Illuminate\Http\Request;

class BatterySettingController extends Controller
{
    public function index($resorId)
    {
        $resor = Resor::findOrFail($resorId);

        $gardu = Gardu::where('resor_id', $resorId)
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
        $request->validate([
            'gardu_id' => 'required|exists:gardu,id',
        ]);

        BatterySetting::updateOrCreate(
            ['gardu_id' => $request->gardu_id],
            $request->except('_token')
        );

        return back()->with('success', 'Battery setting berhasil disimpan');
    }

    public function update(Request $request, $id)
    {
        BatterySetting::findOrFail($id)->update(
            $request->except('_token', '_method')
        );

        return back()->with('success', 'Battery setting berhasil diperbarui');
    }

    public function destroy($gardu_id)
    {
        BatterySetting::where('gardu_id', $gardu_id)->delete();

        return redirect()
            ->back()
            ->with('success', 'Battery setting berhasil dihapus.');
    }
}
