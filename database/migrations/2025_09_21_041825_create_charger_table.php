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
        Schema::create('charger', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no', 50);
            $table->string('merk', 50);
            $table->unsignedSmallInteger('kapasitas');
            $table->date('pemasangan');
            $table->enum('status',['active','fault','offline']);
            $table->foreignId('gardu_id')->constrained('gardu')->onDelete('restrict');
            $table->timestamps();
            $table->index('gardu_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charger');
    }
};
