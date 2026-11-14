<?php

namespace App\Support;

/**
 * Regresi linear sederhana (least squares) untuk memperkirakan kapan nilai melewati batas.
 * x dalam detik (timestamp), y nilai metrik.
 */
class LinearTrend
{
    public function __construct(
        public readonly float $slope,
        public readonly float $intercept,
        public readonly float $r2,
        public readonly int $n,
        public readonly float $lastX,
    ) {
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $points
     */
    public static function fit(array $points): ?self
    {
        $n = count($points);
        if ($n < 2) {
            return null;
        }

        // Geser x ke titik pertama agar angka timestamp tidak kehilangan presisi
        $x0 = $points[0][0];
        $sx = $sy = $sxx = $sxy = $syy = 0.0;
        $lastX = $x0;

        foreach ($points as [$x, $y]) {
            $x -= $x0;
            $sx += $x;
            $sy += $y;
            $sxx += $x * $x;
            $sxy += $x * $y;
            $syy += $y * $y;
            $lastX = max($lastX, $x + $x0);
        }

        $varX = $n * $sxx - $sx * $sx;
        if ($varX <= 0) {
            return null;
        }

        $slope = ($n * $sxy - $sx * $sy) / $varX;
        $intercept = ($sy - $slope * $sx) / $n - $slope * $x0;
        $varY = $n * $syy - $sy * $sy;
        // Data datar sempurna: tidak ada tren, R² dianggap 0
        $r2 = $varY > 0 ? (($n * $sxy - $sx * $sy) ** 2) / ($varX * $varY) : 0.0;

        return new self($slope, $intercept, $r2, $n, $lastX);
    }

    public function valueAt(float $x): float
    {
        return $this->intercept + $this->slope * $x;
    }

    /**
     * Detik sejak titik terakhir sampai garis tren menyentuh batas, null jika tren tidak
     * bergerak menuju batas. 0 jika nilai tren sudah melewati batas.
     */
    public function secondsUntil(float $threshold, string $mode): ?float
    {
        $now = $this->valueAt($this->lastX);
        $crossed = $mode === 'min' ? $now <= $threshold : $now >= $threshold;

        if ($crossed) {
            return 0.0;
        }

        $toward = $mode === 'min' ? $this->slope < 0 : $this->slope > 0;
        if (! $toward) {
            return null;
        }

        return ($threshold - $now) / $this->slope;
    }
}
