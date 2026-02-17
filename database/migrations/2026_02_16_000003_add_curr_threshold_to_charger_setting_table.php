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
        if (! Schema::hasTable('charger_setting')) {
            return;
        }

        Schema::table('charger_setting', function (Blueprint $table) {
            if (! Schema::hasColumn('charger_setting', 'warn_curr')) {
                $table->decimal('warn_curr', 6, 2)->default(0)->after('alarm_max_volt');
            }
            if (! Schema::hasColumn('charger_setting', 'alarm_curr')) {
                $table->decimal('alarm_curr', 6, 2)->default(0)->after('warn_curr');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('charger_setting')) {
            return;
        }

        Schema::table('charger_setting', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('charger_setting', 'warn_curr')) {
                $drops[] = 'warn_curr';
            }
            if (Schema::hasColumn('charger_setting', 'alarm_curr')) {
                $drops[] = 'alarm_curr';
            }

            if (! empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
