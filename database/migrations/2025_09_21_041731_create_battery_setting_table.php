<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battery_setting', function (Blueprint $table) {
            $table->id();

            // ================= VOLTAGE =================
            $table->decimal('volt_min_warn', 6, 2)->nullable();
            $table->decimal('volt_min_alarm', 6, 2)->nullable();
            $table->decimal('volt_max_warn', 6, 2)->nullable();
            $table->decimal('volt_max_alarm', 6, 2)->nullable();

            // ================= TEMPERATURE =================
            $table->decimal('temp_min_warn', 6, 2)->nullable();
            $table->decimal('temp_min_alarm', 6, 2)->nullable();
            $table->decimal('temp_max_warn', 6, 2)->nullable();
            $table->decimal('temp_max_alarm', 6, 2)->nullable();

            // ================= THD =================
            $table->decimal('thd_warn', 6, 2)->nullable();
            $table->decimal('thd_alarm', 6, 2)->nullable();

            // ================= SOC =================
            $table->decimal('soc_warn', 6, 2)->nullable();
            $table->decimal('soc_alarm', 6, 2)->nullable();

            // ================= SOH =================
            $table->decimal('soh_warn', 6, 2)->nullable();
            $table->decimal('soh_alarm', 6, 2)->nullable();

            // ================= RELATION =================
            $table->foreignId('gardu_id')
                ->constrained('gardu')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index('gardu_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battery_settings');
    }
};