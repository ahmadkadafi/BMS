<?php

namespace App\Observers;

use App\Models\BatteryMonitoring;
use App\Services\ThresholdAlarmService;

class BatteryMonitoringObserver
{
    public const CHECKS = [
        ['code' => 'battery_volt_min', 'metric' => 'volt', 'warn' => 'volt_min_warn', 'alarm' => 'volt_min_alarm', 'label' => 'Voltage Min', 'unit' => 'V', 'mode' => 'min'],
        ['code' => 'battery_volt_max', 'metric' => 'volt', 'warn' => 'volt_max_warn', 'alarm' => 'volt_max_alarm', 'label' => 'Voltage Max', 'unit' => 'V', 'mode' => 'max'],
        ['code' => 'battery_temp_min', 'metric' => 'temp', 'warn' => 'temp_min_warn', 'alarm' => 'temp_min_alarm', 'label' => 'Temperature Min', 'unit' => '°C', 'mode' => 'min'],
        ['code' => 'battery_temp_max', 'metric' => 'temp', 'warn' => 'temp_max_warn', 'alarm' => 'temp_max_alarm', 'label' => 'Temperature Max', 'unit' => '°C', 'mode' => 'max'],
        ['code' => 'battery_thd_max', 'metric' => 'thd', 'warn' => 'thd_warn', 'alarm' => 'thd_alarm', 'label' => 'IR', 'unit' => 'mΩ', 'mode' => 'max'],
        ['code' => 'battery_soc_min', 'metric' => 'soc', 'warn' => 'soc_warn', 'alarm' => 'soc_alarm', 'label' => 'SOC', 'unit' => '%', 'mode' => 'min'],
        ['code' => 'battery_soh_min', 'metric' => 'soh', 'warn' => 'soh_warn', 'alarm' => 'soh_alarm', 'label' => 'SOH', 'unit' => '%', 'mode' => 'min'],
    ];

    public function __construct(private ThresholdAlarmService $alarms)
    {
    }

    public function created(BatteryMonitoring $monitoring): void
    {
        $battery = $monitoring->relationLoaded('battery')
            ? $monitoring->battery
            : $monitoring->battery()->with('gardu.resor', 'gardu.batterySetting')->first();

        $gardu = $battery?->gardu;
        $setting = $gardu?->batterySetting;

        if (! $gardu?->resor) {
            return;
        }

        // Jendela debounce: sampel sel ini sampai baris yang sedang diproses
        $window = BatteryMonitoring::query()
            ->where('battery_id', $battery->id)
            ->where('id', '<=', $monitoring->id)
            ->orderByDesc('id')
            ->limit(config('alarm.debounce_samples'))
            ->get(['id', 'measured_at', 'volt', 'temp', 'thd', 'soc', 'soh']);

        $this->alarms->evaluate([
            'device_type' => 'battery',
            'id_column' => 'batt_id',
            'id' => $battery->id,
            'daop_id' => $gardu->resor->daop_id,
            'resor_id' => $gardu->resor->id,
            'gardu_id' => $gardu->id,
        ], self::CHECKS, $setting, $window);
    }
}
