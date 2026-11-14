<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logger', function (Blueprint $table) {
            $table->decimal('last_value', 10, 3)->nullable()->after('alarm_code');
            $table->decimal('peak_value', 10, 3)->nullable()->after('last_value');
            $table->timestamp('escalated_at')->nullable()->after('peak_value');
            $table->timestamp('cleared_at')->nullable()->after('escalated_at');
            $table->index(['device_type', 'alarm_code', 'cleared_at']);
        });

        // Log lama yang sudah ditutup dianggap sudah pulih saat ditutup, agar tidak ikut
        // dilanjutkan oleh gangguan baru. Log open/confirm akan pulih otomatis saat nilai normal.
        DB::table('logger')
            ->where('lifecycle_status', 'closed')
            ->whereNull('cleared_at')
            ->update(['cleared_at' => DB::raw('COALESCE(closed_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('logger', function (Blueprint $table) {
            $table->dropIndex(['device_type', 'alarm_code', 'cleared_at']);
            $table->dropColumn(['last_value', 'peak_value', 'escalated_at', 'cleared_at']);
        });
    }
};
