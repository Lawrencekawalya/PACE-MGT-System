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
        Schema::create('pace_retry_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pace_assignment_id')->constrained()->restrictOnDelete();
            $table->string('assessment_type');
            $table->unsignedSmallInteger('attempt_number');
            $table->string('status')->default('pending');
            $table->boolean('is_over_limit')->default(false);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->text('request_reason');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();
            $table->unique(['pace_assignment_id', 'assessment_type', 'attempt_number']);
            $table->index(['status', 'is_over_limit', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pace_retry_approvals');
    }
};
