<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gardu_id')->constrained('gardu')->cascadeOnDelete();
            $table->date('date');

            foreach (['temp', 'volt_cell', 'thd', 'soc', 'soh', 'voltage_charge', 'current_charge'] as $metric) {
                $table->decimal($metric.'_min', 6, 2)->nullable();
                $table->decimal($metric.'_max', 6, 2)->nullable();
            }

            $table->timestamps();

            $table->unique(['gardu_id', 'date']);
        });

        Schema::table('battery_monitoring', function (Blueprint $table) {
            $table->index('measured_at', 'bm_measured_at_idx');
        });

        Schema::table('charger_monitoring', function (Blueprint $table) {
            $table->index('measured_at', 'cm_measured_at_idx');
        });

        Schema::table('logger', function (Blueprint $table) {
            $table->index(['occurred_at', 'gardu_id', 'status'], 'logger_report_idx');
        });
    }

    public function down(): void
    {
        Schema::table('logger', function (Blueprint $table) {
            $table->dropIndex('logger_report_idx');
        });

        Schema::table('charger_monitoring', function (Blueprint $table) {
            $table->dropIndex('cm_measured_at_idx');
        });

        Schema::table('battery_monitoring', function (Blueprint $table) {
            $table->dropIndex('bm_measured_at_idx');
        });

        Schema::dropIfExists('monitoring_daily_stats');
    }
};
