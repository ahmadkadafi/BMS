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
        'action',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
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
}
