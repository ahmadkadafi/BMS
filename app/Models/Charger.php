<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charger extends Model
{
    protected $table = 'charger';

    protected $fillable = [
        'serial_no',
        'merk',
        'kapasitas',
        'pemasangan',
        'status',
        'gardu_id',
    ];

    public function gardu()
    {
        return $this->belongsTo(Gardu::class);
    }

    public function chargerSetting()
    {
        return $this->hasOne(ChargerSetting::class, 'charger_id');
    }

    public function monitorings()
    {
        return $this->hasMany(ChargerMonitoring::class, 'charger_id');
    }
}
