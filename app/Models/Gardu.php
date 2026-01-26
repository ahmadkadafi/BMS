<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gardu extends Model
{
    protected $table = 'gardu';

    protected $fillable = [
        'kode',
        'nama',
        'n_bank',
        'n_batt',
        'address',
        'latitude',
        'longitude',
        'resor_id'
    ];

    public function batteries()
    {
        return $this->hasMany(Battery::class);
    }

    public function batterySetting()
    {
        return $this->hasOne(BatterySetting::class, 'gardu_id');
    }

    public function resor()
    {
        return $this->belongsTo(Resor::class);
    }
}