<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatteryDaily extends Model
{
    protected $table = 'battery_daily';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function battery()
    {
        return $this->belongsTo(Battery::class);
    }
}
