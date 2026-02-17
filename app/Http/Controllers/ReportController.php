<?php

namespace App\Http\Controllers;

use App\Models\BatteryMonitoring;
use App\Models\ChargerMonitoring;
use App\Models\Gardu;
use App\Models\Logger;
use App\Models\Resor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function report(Request $request): RedirectResponse
    {
        $authUser = $request->session()->get('auth_user', []);

        $targetResorId = $authUser['allowed_resor_id'] ?? Resor::query()->orderBy('id')->value('id');

        if (! $targetResorId) {
            abort(404, 'Data resor belum tersedia.');
        }

        return redirect()->route('report.resor', ['resor' => $targetResorId]);
    }

    public function index(Request $request, Resor $resor): View
    {
        $month = max(1, min(12, (int) $request->input('month', now()->month)));
        $year = max(2000, min(2100, (int) $request->input('year', now()->year)));
        $referenceDate = Carbon::create($year, $month, 1)->endOfMonth();

        $gardus = Gardu::query()
            ->where('resor_id', $resor->id)
            ->with(['batteries:id,serial_no,pemasangan,gardu_id', 'chargers:id,gardu_id'])
            ->orderBy('nama')
            ->get();

        $reportCards = $gardus->map(function ($gardu) use ($month, $year, $referenceDate) {
            $batteryIds = $gardu->batteries->pluck('id');
            $chargerIds = $gardu->chargers->pluck('id');

            $batteryStats = $batteryIds->isEmpty()
                ? null
                : BatteryMonitoring::query()
                    ->whereIn('battery_id', $batteryIds)
                    ->whereYear('measured_at', $year)
                    ->whereMonth('measured_at', $month)
                    ->selectRaw('MAX(temp) as max_temp, MIN(temp) as min_temp, MAX(volt) as max_volt_cell, MIN(volt) as min_volt_cell, MAX(thd) as max_thd, MIN(thd) as min_thd')
                    ->first();

            $chargerStats = $chargerIds->isEmpty()
                ? null
                : ChargerMonitoring::query()
                    ->whereIn('charger_id', $chargerIds)
                    ->whereYear('measured_at', $year)
                    ->whereMonth('measured_at', $month)
                    ->selectRaw('MAX(voltage) as max_voltage_charge, MIN(voltage) as min_voltage_charge, MAX(current) as max_current_charge, MIN(current) as min_current_charge')
                    ->first();

            $alertCounts = Logger::query()
                ->where('gardu_id', $gardu->id)
                ->whereYear('occurred_at', $year)
                ->whereMonth('occurred_at', $month)
                ->selectRaw("SUM(CASE WHEN status='warning' THEN 1 ELSE 0 END) as warning_count")
                ->selectRaw("SUM(CASE WHEN status='alarm' THEN 1 ELSE 0 END) as alarm_count")
                ->first();

            $remainingDays = $gardu->batteries
                ->filter(fn ($b) => ! empty($b->pemasangan))
                ->map(function ($battery) use ($referenceDate) {
                    $expiredAt = Carbon::parse($battery->pemasangan)->addYears(2);
                    return $referenceDate->diffInDays($expiredAt, false);
                })
                ->min();

            return [
                'gardu' => $gardu,
                'remaining_days' => $remainingDays,
                'warning_count' => (int) ($alertCounts->warning_count ?? 0),
                'alarm_count' => (int) ($alertCounts->alarm_count ?? 0),
                'max_temp' => $batteryStats?->max_temp,
                'min_temp' => $batteryStats?->min_temp,
                'max_volt_cell' => $batteryStats?->max_volt_cell,
                'min_volt_cell' => $batteryStats?->min_volt_cell,
                'max_thd' => $batteryStats?->max_thd,
                'min_thd' => $batteryStats?->min_thd,
                'max_voltage_charge' => $chargerStats?->max_voltage_charge,
                'min_voltage_charge' => $chargerStats?->min_voltage_charge,
                'max_current_charge' => $chargerStats?->max_current_charge,
                'min_current_charge' => $chargerStats?->min_current_charge,
            ];
        });

        return view('page.report', [
            'resor' => $resor,
            'month' => $month,
            'year' => $year,
            'years' => range(now()->year - 5, now()->year + 1),
            'reportCards' => $reportCards,
        ]);
    }
}
