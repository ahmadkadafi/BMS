<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('charger_setting');

        Schema::create('charger_setting', function (Blueprint $table) {
            $table->id();
            $table->decimal('float', 6, 2);
            $table->decimal('boost', 6, 2);
            $table->decimal('warn_min_volt', 6, 2);
            $table->decimal('warn_max_volt', 6, 2);
            $table->decimal('alarm_min_volt', 6, 2);
            $table->decimal('alarm_max_volt', 6, 2);
            $table->decimal('warn_curr', 6, 2);
            $table->decimal('alarm_curr', 6, 2);
            $table->foreignId('charger_id')->constrained('charger')->cascadeOnDelete();
            $table->timestamps();
            $table->index('charger_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charger_setting');
    }
};
