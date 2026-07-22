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
        Schema::create('pace_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_course_id')->constrained()->restrictOnDelete();
            $table->foreignId('pace_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('assigned')->index();
            $table->unsignedSmallInteger('attempt_cycle')->default(1);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reassigned_at')->nullable();
            $table->text('override_reason')->nullable();
            $table->timestamps();
            $table->unique(['student_course_id', 'pace_id', 'attempt_cycle']);
            $table->index(['academic_year_id', 'term_id', 'status']);
            $table->index(['student_course_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pace_assignments');
    }
};
