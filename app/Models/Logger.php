<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logger extends Model
{
    protected $table = 'logger';

    protected $fillable = [
        'occurred_at',
        'device_type',
        'daop_id',
        'resor_id',
        'gardu_id',
        'batt_id',
        'charger_id',
        'message',
        'status',
        'alarm_type',
        'alarm_code',
        'last_value',
        'peak_value',
        'escalated_at',
        'cleared_at',
        'lifecycle_status',
        'confirmed_at',
        'closed_at',
        'action',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'escalated_at' => 'datetime',
        'cleared_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function resor()
    {
        return $this->belongsTo(Resor::class);
    }

    public function gardu()
    {
        return $this->belongsTo(Gardu::class);
    }

    public function battery()
    {
        return $this->belongsTo(Battery::class, 'batt_id');
    }

    public function charger()
    {
        return $this->belongsTo(Charger::class, 'charger_id');
    }

    /**
     * Lama gangguan: dari kejadian sampai nilai pulih, atau sampai sekarang jika masih berlangsung.
     * Log non-threshold tanpa siklus pulih memakai waktu close operator.
     */
    public function getDurationTextAttribute(): ?string
    {
        if (! $this->occurred_at) {
            return null;
        }

        $end = $this->cleared_at ?? ($this->alarm_code ? now() : $this->closed_at);
        if (! $end) {
            return null;
        }

        return $this->formatDuration((int) abs($this->occurred_at->diffInSeconds($end)));
    }

    // Waktu respons operator: dari kejadian sampai confirm
    public function getResponseTextAttribute(): ?string
    {
        if (! $this->occurred_at || ! $this->confirmed_at) {
            return null;
        }

        return $this->formatDuration((int) abs($this->occurred_at->diffInSeconds($this->confirmed_at)));
    }

    public function isOngoing(): bool
    {
        return $this->alarm_code && ! $this->cleared_at;
    }

    private function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days} hari";
        }
        if ($hours > 0) {
            $parts[] = "{$hours} jam";
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = "{$minutes} menit";
        }

        return implode(' ', $parts);
    }
}
