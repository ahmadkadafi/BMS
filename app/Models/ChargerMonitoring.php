<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargerMonitoring extends Model
{
    protected $table = 'charger_monitoring';

    protected $casts = [
        'measured_at' => 'datetime',
    ];

    public function charger()
    {
        return $this->belongsTo(Charger::class);
    }
}
