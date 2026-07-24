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
            $table->foreignId('previous_enrollment_id')
                ->nullable()
                ->after('student_id')
                ->unique('student_enrollments_previous_unique')
                ->constrained('student_enrollments')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropForeign(['previous_enrollment_id']);
            $table->dropUnique('student_enrollments_previous_unique');
            $table->dropColumn('previous_enrollment_id');
        });
    }
};
