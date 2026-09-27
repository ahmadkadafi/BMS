<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logger_sync_state', function (Blueprint $table) {
            $table->id();
            $table->string('source')->unique();
            $table->unsignedBigInteger('last_id')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logger_sync_state');
    }
};
