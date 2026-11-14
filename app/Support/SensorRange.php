<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;

/**
 * Rentang nilai sensor yang masuk akal dari config alarm.valid_ranges.
 */
class SensorRange
{
    public static function get(string $device, string $metric): ?array
    {
        return config("alarm.valid_ranges.{$device}.{$metric}");
    }

    public static function isFault(string $device, string $metric, $value): bool
    {
        $range = self::get($device, $metric);

        if (! $range) {
            return false;
        }

        return is_null($value) || (float) $value < $range['min'] || (float) $value > $range['max'];
    }

    /**
     * Batasi query ke baris yang semua metriknya (yang disebut) di dalam rentang.
     *
     * @param  array  $columns  metric => nama kolom di query
     */
    public static function whereValid(Builder $query, string $device, array $columns): Builder
    {
        foreach ($columns as $metric => $column) {
            if ($range = self::get($device, $metric)) {
                $query->whereBetween($column, [$range['min'], $range['max']]);
            }
        }

        return $query;
    }
}
