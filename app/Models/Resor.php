<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resor extends Model
{
    protected $table = 'resor';

    protected $fillable = [
        'nama',
        'alamat',
        'n_asset',
        'daop_id',
    ];

    public function daop()
    {
        return $this->belongsTo(Daop::class, 'daop_id');
    }

    public function gardu()
    {
        return $this->hasMany(Gardu::class);
    }
}
