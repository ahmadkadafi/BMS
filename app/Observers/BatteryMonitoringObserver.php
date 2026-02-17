<?php

namespace App\Observers;

use App\Models\BatteryMonitoring;
use App\Models\Logger;

class BatteryMonitoringObserver
{
    public function created(BatteryMonitoring $monitoring): void
    {
        $battery = $monitoring->battery()->with('gardu.resor', 'gardu.batterySetting')->first();
        if (! $battery || ! $battery->gardu || ! $battery->gardu->resor) {
            return;
        }

        $gardu = $battery->gardu;
        $resor = $gardu->resor;
        $setting = $gardu->batterySetting;

        if (! $setting) {
            return;
        }

        $checks = [
            ['metric' => 'volt', 'warn' => 'volt_min_warn', 'alarm' => 'volt_min_alarm', 'label' => 'Voltage Min', 'unit' => 'V', 'mode' => 'min'],
            ['metric' => 'volt', 'warn' => 'volt_max_warn', 'alarm' => 'volt_max_alarm', 'label' => 'Voltage Max', 'unit' => 'V', 'mode' => 'max'],
            ['metric' => 'temp', 'warn' => 'temp_min_warn', 'alarm' => 'temp_min_alarm', 'label' => 'Temperature Min', 'unit' => 'C', 'mode' => 'min'],
            ['metric' => 'temp', 'warn' => 'temp_max_warn', 'alarm' => 'temp_max_alarm', 'label' => 'Temperature Max', 'unit' => 'C', 'mode' => 'max'],
            ['metric' => 'thd', 'warn' => 'thd_warn', 'alarm' => 'thd_alarm', 'label' => 'THD', 'unit' => '%', 'mode' => 'max'],
            ['metric' => 'soc', 'warn' => 'soc_warn', 'alarm' => 'soc_alarm', 'label' => 'SOC', 'unit' => '%', 'mode' => 'min'],
            ['metric' => 'soh', 'warn' => 'soh_warn', 'alarm' => 'soh_alarm', 'label' => 'SOH', 'unit' => '%', 'mode' => 'min'],
        ];

        foreach ($checks as $check) {
            $result = $this->evaluateThreshold(
                (float) $monitoring->{$check['metric']},
                $setting->{$check['warn']},
                $setting->{$check['alarm']},
                $check['mode']
            );

            if (! $result) {
                continue;
            }

            $message = $this->buildMessage(
                $check['label'],
                (float) $monitoring->{$check['metric']},
                $check['unit'],
                $result['level'],
                (float) $result['threshold'],
                $check['mode']
            );

            Logger::firstOrCreate(
                [
                    'occurred_at' => $monitoring->measured_at,
                    'batt_id' => $battery->id,
                    'message' => $message,
                ],
                [
                    'device_type' => 'battery',
                    'daop_id' => $resor->daop_id,
                    'resor_id' => $resor->id,
                    'gardu_id' => $gardu->id,
                    'charger_id' => null,
                    'status' => $result['level'],
                    'action' => 'coming',
                ]
            );
        }
    }

    private function evaluateThreshold(float $value, $warnThreshold, $alarmThreshold, string $mode): ?array
    {
        $warn = is_null($warnThreshold) ? null : (float) $warnThreshold;
        $alarm = is_null($alarmThreshold) ? null : (float) $alarmThreshold;

        if ($mode === 'min') {
            if (! is_null($alarm) && $value <= $alarm) {
                return ['level' => 'alarm', 'threshold' => $alarm];
            }
            if (! is_null($warn) && $value <= $warn) {
                return ['level' => 'warning', 'threshold' => $warn];
            }

            return null;
        }

        if (! is_null($alarm) && $value >= $alarm) {
            return ['level' => 'alarm', 'threshold' => $alarm];
        }
        if (! is_null($warn) && $value >= $warn) {
            return ['level' => 'warning', 'threshold' => $warn];
        }

        return null;
    }

    private function buildMessage(
        string $label,
        float $value,
        string $unit,
        string $level,
        float $threshold,
        string $mode
    ): string {
        $condition = $mode === 'min' ? 'di bawah' : 'di atas';

        return sprintf(
            '%s %.2f %s %s batas %s %.2f %s',
            $label,
            $value,
            $unit,
            $condition,
            strtoupper($level),
            $threshold,
            $unit
        );
    }
}
