<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Battery extends Model
{
    protected $table = 'battery';

    public function gardu()
    {
        return $this->belongsTo(Gardu::class);
    }

    public function monitorings()
    {
        return $this->hasMany(BatteryMonitoring::class);
    }
}
