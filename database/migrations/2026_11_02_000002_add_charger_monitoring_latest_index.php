<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charger_monitoring', function (Blueprint $table) {
            $table->index(['charger_id', 'measured_at', 'id'], 'cm_latest_measurement_idx');
        });
    }

    public function down(): void
    {
        Schema::table('charger_monitoring', function (Blueprint $table) {
            $table->dropIndex('cm_latest_measurement_idx');
        });
    }
};
