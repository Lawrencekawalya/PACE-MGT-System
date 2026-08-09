<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->decimal('pace_cost', 12, 2)->default(0)->after('is_closed');
        });

        $legacyCost = DB::table('school_settings')->where('id', 1)->value('pace_cost');
        if ((float) $legacyCost > 0) {
            DB::table('terms')->where('is_active', true)->update(['pace_cost' => $legacyCost]);
        }

        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn('pace_cost');
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->decimal('pace_cost', 12, 2)->default(0)->after('term_pace_target');
        });

        $activeCost = DB::table('terms')->where('is_active', true)->value('pace_cost');
        if ((float) $activeCost > 0) {
            DB::table('school_settings')->where('id', 1)->update(['pace_cost' => $activeCost]);
        }

        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('pace_cost');
        });
    }
};
