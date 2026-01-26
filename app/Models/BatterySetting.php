<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatterySetting extends Model
{
    protected $table = 'battery_setting';
    protected $guarded = [];

    protected $fillable = [
        'gardu_id',
        'volt_min_warn','volt_min_alarm','volt_max_warn','volt_max_alarm',
        'temp_min_warn','temp_min_alarm','temp_max_warn','temp_max_alarm',
        'thd_warn','thd_alarm',
        'soc_warn','soc_alarm',
        'soh_warn','soh_alarm',
    ];

    public function gardu()
    {
        return $this->belongsTo(Gardu::class);
    }
}
