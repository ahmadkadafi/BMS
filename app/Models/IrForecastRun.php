<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrForecastRun extends Model
{
    protected $table = 'ir_forecast_run';

    protected $guarded = [];

    protected $casts = [
        'anchor_date' => 'date',
        'skipped' => 'array',
    ];
}
