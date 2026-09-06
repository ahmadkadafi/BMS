<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('logger', 'alarm_code')) {
            Schema::table('logger', function (Blueprint $table) {
                $table->string('alarm_code', 80)->nullable()->after('alarm_type');
                $table->index(['device_type', 'alarm_code', 'lifecycle_status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('logger', 'alarm_code')) {
            Schema::table('logger', function (Blueprint $table) {
                $table->dropIndex(['device_type', 'alarm_code', 'lifecycle_status']);
                $table->dropColumn('alarm_code');
            });
        }
    }
};
