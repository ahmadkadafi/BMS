<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Daop extends Model
{
    protected $table = 'daop';

    protected $fillable = [
        'nama',
        'wilayah',
    ];

    public function resors()
    {
        return $this->hasMany(Resor::class, 'daop_id');
    }
}
