<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battery_monitoring', function (Blueprint $table) {
            $table->index(['battery_id', 'measured_at', 'id'], 'bm_latest_measurement_idx');
        });

        Schema::table('logger', function (Blueprint $table) {
            $table->index(['action', 'status', 'resor_id'], 'logger_notification_count_idx');
            $table->index(['action', 'occurred_at', 'status'], 'logger_notification_latest_idx');
            $table->index(['resor_id', 'action', 'occurred_at', 'status'], 'logger_resor_notification_latest_idx');
        });
    }

    public function down(): void
    {
        Schema::table('logger', function (Blueprint $table) {
            $table->dropIndex('logger_notification_count_idx');
            $table->dropIndex('logger_notification_latest_idx');
            $table->dropIndex('logger_resor_notification_latest_idx');
        });

        Schema::table('battery_monitoring', function (Blueprint $table) {
            $table->dropIndex('bm_latest_measurement_idx');
        });
    }
};
