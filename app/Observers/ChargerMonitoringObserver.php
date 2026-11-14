<?php

namespace App\Observers;

use App\Models\ChargerMonitoring;
use App\Services\ThresholdAlarmService;

class ChargerMonitoringObserver
{
    public const CHECKS = [
        ['code' => 'charger_voltage_min', 'metric' => 'voltage', 'warn' => 'warn_min_volt', 'alarm' => 'alarm_min_volt', 'label' => 'Charger Voltage Min', 'unit' => 'V', 'mode' => 'min'],
        ['code' => 'charger_voltage_max', 'metric' => 'voltage', 'warn' => 'warn_max_volt', 'alarm' => 'alarm_max_volt', 'label' => 'Charger Voltage Max', 'unit' => 'V', 'mode' => 'max'],
        ['code' => 'charger_current_max', 'metric' => 'current', 'warn' => 'warn_curr', 'alarm' => 'alarm_curr', 'label' => 'Charger Current', 'unit' => 'A', 'mode' => 'max'],
    ];

    public function __construct(private ThresholdAlarmService $alarms)
    {
    }

    public function created(ChargerMonitoring $monitoring): void
    {
        $charger = $monitoring->relationLoaded('charger')
            ? $monitoring->charger
            : $monitoring->charger()->with('gardu.resor', 'chargerSetting')->first();

        $gardu = $charger?->gardu;
        $setting = $charger?->chargerSetting;

        if (! $gardu?->resor) {
            return;
        }

        $window = ChargerMonitoring::query()
            ->where('charger_id', $charger->id)
            ->where('id', '<=', $monitoring->id)
            ->orderByDesc('id')
            ->limit(config('alarm.debounce_samples'))
            ->get(['id', 'measured_at', 'voltage', 'current']);

        $this->alarms->evaluate([
            'device_type' => 'charger',
            'id_column' => 'charger_id',
            'id' => $charger->id,
            'daop_id' => $gardu->resor->daop_id,
            'resor_id' => $gardu->resor->id,
            'gardu_id' => $gardu->id,
        ], self::CHECKS, $setting, $window);
    }
}
