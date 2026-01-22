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
        Schema::create('resor', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('alamat', 300);
            $table->integer('n_asset');
            $table->foreignId('daop_id')->constrained('daop')->onDelete('restrict'); 
            $table->timestamps();
            $table->index('daop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resor');
    }
};
