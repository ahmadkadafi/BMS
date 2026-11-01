<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrForecast extends Model
{
    protected $table = 'ir_forecast';

    protected $guarded = [];

    protected $casts = [
        'anchor_date' => 'date',
        'target_date' => 'date',
    ];

    public function battery()
    {
        return $this->belongsTo(Battery::class);
    }
}
