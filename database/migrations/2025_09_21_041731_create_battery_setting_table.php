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
        Schema::create('battery_setting', function (Blueprint $table) {
            $table->id();
            $table->decimal('v_max',6,2);
            $table->decimal('v_min',6,2);
            $table->decimal('t_max',6,2);
            $table->decimal('t_min',6,2);
            $table->decimal('rint_max',6,2);
            $table->decimal('soc_min',6,2);
            $table->decimal('soh_min',6,2);
            $table->foreignId('battery_id')->constrained('battery')->onDelete('cascade');
            $table->timestamps();
            $table->index('battery_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battery_setting');
    }
};
