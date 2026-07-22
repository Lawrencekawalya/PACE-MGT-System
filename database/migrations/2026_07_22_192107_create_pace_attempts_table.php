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
        Schema::create('pace_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pace_assignment_id')->constrained()->restrictOnDelete();
            $table->string('assessment_type');
            $table->unsignedSmallInteger('attempt_number');
            $table->decimal('score', 5, 2);
            $table->decimal('pass_mark_used', 5, 2);
            $table->string('outcome');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_reason')->nullable();
            $table->timestamp('finalized_at');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['pace_assignment_id', 'assessment_type', 'attempt_number']);
            $table->index(['assessment_type', 'outcome', 'finalized_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pace_attempts');
    }
};
