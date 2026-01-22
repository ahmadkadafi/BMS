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
        Schema::create('gardu', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100);
            $table->string('nama', 100);
            $table->unsignedSmallInteger('n_bank');
            $table->unsignedSmallInteger('n_batt');
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->foreignId('resor_id')->constrained('resor')->onDelete('restrict');
            $table->timestamps();
            $table->index('resor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gardu');
    }
};
