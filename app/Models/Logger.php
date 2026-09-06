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
        'lifecycle_status',
        'confirmed_at',
        'closed_at',
        'action',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
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

    public function getDurationTextAttribute(): ?string
    {
        if (! $this->occurred_at || ! $this->closed_at) {
            return null;
        }

        $seconds = (int) abs($this->occurred_at->diffInSeconds($this->closed_at));
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
