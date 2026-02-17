<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargerSetting extends Model
{
    protected $table = 'charger_setting';

    protected $fillable = [
        'float',
        'boost',
        'warn_min_volt',
        'warn_max_volt',
        'alarm_min_volt',
        'alarm_max_volt',
        'warn_curr',
        'alarm_curr',
        'charger_id',
    ];

    public function charger()
    {
        return $this->belongsTo(Charger::class, 'charger_id');
    }
}
