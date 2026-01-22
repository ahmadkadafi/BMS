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
        Schema::create('battery_monitoring', function (Blueprint $table) {
            $table->id();
            $table->timestamp('measured_at');
            $table->decimal('volt',6,2);
            $table->decimal('thd',6,2);
            $table->decimal('temp',6,2);
            $table->decimal('soc',6,2);
            $table->decimal('soh',6,2);
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
        Schema::dropIfExists('battery_monitoring');
    }
};
