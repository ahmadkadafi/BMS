<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logger', function (Blueprint $table) {
            $table->string('alarm_type', 30)->nullable()->after('status');
            $table->string('alarm_code', 80)->nullable()->after('alarm_type');
            $table->string('lifecycle_status', 30)->default('open')->after('alarm_code');
            $table->timestamp('confirmed_at')->nullable()->after('lifecycle_status');
            $table->timestamp('closed_at')->nullable()->after('confirmed_at');
            $table->index(['alarm_type', 'lifecycle_status']);
            $table->index(['device_type', 'alarm_code', 'lifecycle_status']);
        });

        DB::table('logger')->update([
            'alarm_type' => DB::raw('status'),
        ]);

        DB::table('logger')
            ->where('action', 'coming')
            ->update(['lifecycle_status' => 'open']);

        DB::table('logger')
            ->where('action', 'handled')
            ->update(['lifecycle_status' => 'confirm']);

        DB::table('logger')
            ->where('action', 'released')
            ->update([
                'lifecycle_status' => 'closed',
                'closed_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('logger', function (Blueprint $table) {
            $table->dropIndex(['alarm_type', 'lifecycle_status']);
            $table->dropIndex(['device_type', 'alarm_code', 'lifecycle_status']);
            $table->dropColumn([
                'alarm_type',
                'alarm_code',
                'lifecycle_status',
                'confirmed_at',
                'closed_at',
            ]);
        });
    }
};
