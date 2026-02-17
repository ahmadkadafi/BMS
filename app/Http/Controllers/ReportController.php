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

            $batteryBase = BatteryMonitoring::query()
                ->whereIn('battery_id', $batteryIds)
                ->whereYear('measured_at', $year)
                ->whereMonth('measured_at', $month);

            $chargerBase = ChargerMonitoring::query()
                ->whereIn('charger_id', $chargerIds)
                ->whereYear('measured_at', $year)
                ->whereMonth('measured_at', $month);

            $pickExtreme = function ($baseQuery, string $field, string $direction) {
                $row = (clone $baseQuery)
                    ->whereNotNull($field)
                    ->orderBy($field, $direction)
                    ->orderBy('measured_at', $direction)
                    ->first([$field, 'measured_at']);

                if (! $row) {
                    return ['value' => null, 'at' => null];
                }

                return [
                    'value' => $row->{$field},
                    'at' => $row->measured_at,
                ];
            };

            $minTemp = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'temp', 'asc');
            $maxTemp = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'temp', 'desc');
            $minVoltCell = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'volt', 'asc');
            $maxVoltCell = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'volt', 'desc');
            $minThd = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'thd', 'asc');
            $maxThd = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'thd', 'desc');
            $minSoc = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'soc', 'asc');
            $maxSoc = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'soc', 'desc');
            $minSoh = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'soh', 'asc');
            $maxSoh = $batteryIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($batteryBase, 'soh', 'desc');

            $minVoltCharge = $chargerIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($chargerBase, 'voltage', 'asc');
            $maxVoltCharge = $chargerIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($chargerBase, 'voltage', 'desc');
            $minCurrentCharge = $chargerIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($chargerBase, 'current', 'asc');
            $maxCurrentCharge = $chargerIds->isEmpty() ? ['value' => null, 'at' => null] : $pickExtreme($chargerBase, 'current', 'desc');

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
                'max_temp' => $maxTemp['value'],
                'min_temp' => $minTemp['value'],
                'max_temp_at' => $maxTemp['at'],
                'min_temp_at' => $minTemp['at'],
                'max_volt_cell' => $maxVoltCell['value'],
                'min_volt_cell' => $minVoltCell['value'],
                'max_volt_cell_at' => $maxVoltCell['at'],
                'min_volt_cell_at' => $minVoltCell['at'],
                'max_thd' => $maxThd['value'],
                'min_thd' => $minThd['value'],
                'max_thd_at' => $maxThd['at'],
                'min_thd_at' => $minThd['at'],
                'max_soc' => $maxSoc['value'],
                'min_soc' => $minSoc['value'],
                'max_soc_at' => $maxSoc['at'],
                'min_soc_at' => $minSoc['at'],
                'max_soh' => $maxSoh['value'],
                'min_soh' => $minSoh['value'],
                'max_soh_at' => $maxSoh['at'],
                'min_soh_at' => $minSoh['at'],
                'max_voltage_charge' => $maxVoltCharge['value'],
                'min_voltage_charge' => $minVoltCharge['value'],
                'max_voltage_charge_at' => $maxVoltCharge['at'],
                'min_voltage_charge_at' => $minVoltCharge['at'],
                'max_current_charge' => $maxCurrentCharge['value'],
                'min_current_charge' => $minCurrentCharge['value'],
                'max_current_charge_at' => $maxCurrentCharge['at'],
                'min_current_charge_at' => $minCurrentCharge['at'],
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
