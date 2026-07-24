<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'storekeeper')
            ->update([
                'name' => 'pace_officer',
                'display_name' => 'PACE Officer',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'pace_officer')
            ->update([
                'name' => 'storekeeper',
                'display_name' => 'Storekeeper',
                'updated_at' => now(),
            ]);
    }
};
