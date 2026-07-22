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
        Schema::table('pace_assignments', function (Blueprint $table) {
            $table->index(['status', 'assigned_at']);
            $table->index(['status', 'submitted_at']);
            $table->index(['status', 'completed_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['type', 'recorded_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('catalogue_imports', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pace_assignments', function (Blueprint $table) {
            $table->dropIndex(['status', 'assigned_at']);
            $table->dropIndex(['status', 'submitted_at']);
            $table->dropIndex(['status', 'completed_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['type', 'recorded_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('catalogue_imports', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
