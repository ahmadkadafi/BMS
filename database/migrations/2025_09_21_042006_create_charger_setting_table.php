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
        Schema::create('charger_setting', function (Blueprint $table) {
            $table->id();
            $table->decimal('float',6,2);
            $table->decimal('boost',6,2);
            $table->decimal('max_volt',6,2);
            $table->decimal('min_volt',6,2);
            $table->decimal('max_cur',6,2);
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
        Schema::dropIfExists('charger_setting');
    }
};
