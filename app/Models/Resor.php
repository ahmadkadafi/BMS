<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resor extends Model
{
    protected $table = 'resor';

    public function gardu()
    {
        return $this->hasMany(Gardu::class);
    }
}
