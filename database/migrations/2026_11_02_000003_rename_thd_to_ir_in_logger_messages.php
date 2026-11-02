<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Pesan logger lama "THD 5.00 % di atas batas ... %" disamakan dengan label baru IR (mΩ)
    public function up(): void
    {
        DB::table('logger')
            ->where('alarm_code', 'battery_thd_max')
            ->update(['message' => DB::raw("REPLACE(REPLACE(message, ' %', ' mΩ'), 'THD ', 'IR ')")]);
    }

    public function down(): void
    {
        DB::table('logger')
            ->where('alarm_code', 'battery_thd_max')
            ->update(['message' => DB::raw("REPLACE(REPLACE(message, ' mΩ', ' %'), 'IR ', 'THD ')")]);
    }
};
