<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battery_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battery_id')->constrained('battery')->cascadeOnDelete();
            $table->foreignId('gardu_id')->constrained('gardu')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('ir', 8, 3);
            $table->decimal('volt', 8, 4);
            $table->decimal('temp', 8, 4);
            $table->decimal('v_charger', 8, 4)->nullable();
            $table->decimal('i_mean', 8, 4)->nullable();
            $table->decimal('i_min', 8, 4)->nullable();
            $table->unsignedSmallInteger('samples');
            $table->timestamps();

            $table->unique(['battery_id', 'date']);
            $table->index(['gardu_id', 'date']);
        });

        Schema::create('ir_forecast', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battery_id')->constrained('battery')->cascadeOnDelete();
            $table->foreignId('gardu_id')->constrained('gardu')->cascadeOnDelete();
            $table->date('anchor_date');
            $table->unsignedTinyInteger('horizon');
            $table->date('target_date');
            $table->string('model', 30);
            $table->decimal('r_anchor', 8, 3);
            $table->decimal('r_pred', 8, 4);
            $table->decimal('r_low', 8, 4)->nullable();
            $table->decimal('r_high', 8, 4)->nullable();
            $table->decimal('seed_std', 8, 4);
            $table->unsignedSmallInteger('flat_days');
            $table->decimal('max_z', 8, 2);
            $table->string('quality', 12);
            $table->timestamps();

            $table->unique(['battery_id', 'anchor_date', 'horizon']);
            $table->index(['gardu_id', 'anchor_date']);
            $table->index(['gardu_id', 'target_date']);
        });

        Schema::create('ir_forecast_run', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gardu_id')->constrained('gardu')->cascadeOnDelete();
            $table->date('anchor_date');
            $table->string('status', 10);
            $table->unsignedSmallInteger('n_forecast')->default(0);
            $table->json('skipped')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['gardu_id', 'anchor_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ir_forecast_run');
        Schema::dropIfExists('ir_forecast');
        Schema::dropIfExists('battery_daily');
    }
};
