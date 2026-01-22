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
        Schema::create('charger_monitoring', function (Blueprint $table) {
            $table->id();
            $table->timestamp('measured_at');
            $table->decimal('voltage',6,2);
            $table->decimal('current',6,2);
            $table->foreignId('charger_id')->nullable()->constrained('charger')->nullOnDelete();
            $table->timestamps();
            $table->index('charger_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charger_monitoring');
    }
};
