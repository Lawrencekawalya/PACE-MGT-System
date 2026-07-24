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
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreignId('learning_center_id')
                ->nullable()
                ->after('student_id')
                ->constrained()
                ->restrictOnDelete();
            $table->index(['learning_center_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropIndex(['learning_center_id', 'status']);
            $table->dropConstrainedForeignId('learning_center_id');
        });
    }
};
