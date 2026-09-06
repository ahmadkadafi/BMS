<?php

namespace App\Observers;

use App\Models\ChargerMonitoring;
use App\Models\Logger;

class ChargerMonitoringObserver
{
    public function created(ChargerMonitoring $monitoring): void
    {
        $charger = $monitoring->relationLoaded('charger')
            ? $monitoring->charger
            : $monitoring->charger()->with('gardu.resor', 'chargerSetting')->first();

        if (! $charger || ! $charger->gardu || ! $charger->gardu->resor) {
            return;
        }

        $gardu = $charger->gardu;
        $resor = $gardu->resor;
        $setting = $charger->chargerSetting;

        if (! $setting) {
            return;
        }

        $checks = [
            ['code' => 'charger_voltage_min', 'metric' => 'voltage', 'warn' => 'warn_min_volt', 'alarm' => 'alarm_min_volt', 'label' => 'Charger Voltage Min', 'unit' => 'V', 'mode' => 'min'],
            ['code' => 'charger_voltage_max', 'metric' => 'voltage', 'warn' => 'warn_max_volt', 'alarm' => 'alarm_max_volt', 'label' => 'Charger Voltage Max', 'unit' => 'V', 'mode' => 'max'],
            ['code' => 'charger_current_max', 'metric' => 'current', 'warn' => 'warn_curr', 'alarm' => 'alarm_curr', 'label' => 'Charger Current', 'unit' => 'A', 'mode' => 'max'],
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

            $ongoingLog = Logger::query()
                ->where('device_type', 'charger')
                ->where('charger_id', $charger->id)
                ->where('alarm_code', $check['code'])
                ->whereIn('lifecycle_status', ['open', 'confirm'])
                ->first();

            if ($ongoingLog) {
                $ongoingLog->update([
                    'message' => $message,
                    'status' => $result['level'],
                    'alarm_type' => $result['level'],
                ]);

                continue;
            }

            Logger::create(
                [
                    'occurred_at' => $monitoring->measured_at,
                    'device_type' => 'charger',
                    'daop_id' => $resor->daop_id,
                    'resor_id' => $resor->id,
                    'gardu_id' => $gardu->id,
                    'batt_id' => null,
                    'charger_id' => $charger->id,
                    'message' => $message,
                    'status' => $result['level'],
                    'alarm_type' => $result['level'],
                    'alarm_code' => $check['code'],
                    'lifecycle_status' => 'open',
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
