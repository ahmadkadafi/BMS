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
        Schema::create('logger', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at');
            $table->enum('device_type',['battery','charger','other']);
            $table->foreignId('daop_id')->nullable()->constrained('daop')->nullOnDelete();
            $table->foreignId('resor_id')->nullable()->constrained('resor')->nullOnDelete();
            $table->foreignId('gardu_id')->nullable()->constrained('gardu')->nullOnDelete();
            $table->foreignId('batt_id')->nullable()->constrained('battery')->nullOnDelete();
            $table->foreignId('charger_id')->nullable()->constrained('charger')->nullOnDelete();
            $table->string('message',300);
            $table->enum('status',['info','warning','alarm']);
            $table->enum('action',['coming','handled','released']);
            $table->timestamps();
            $table->index(['daop_id','resor_id','gardu_id','batt_id','charger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logger');
    }
};
