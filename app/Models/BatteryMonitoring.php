<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatteryMonitoring extends Model
{
    protected $table = 'battery_monitoring';

    public function battery()
    {
        return $this->belongsTo(Battery::class);
    }
}
