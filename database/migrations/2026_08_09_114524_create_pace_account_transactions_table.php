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
        Schema::table('school_settings', function (Blueprint $table) {
            $table->decimal('pace_cost', 12, 2)->default(0)->after('term_pace_target');
        });

        Schema::create('pace_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('pace_assignment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['student_id', 'recorded_at'], 'pace_account_student_recorded_index');
            $table->unique(['pace_assignment_id', 'type'], 'pace_account_assignment_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pace_account_transactions');

        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn('pace_cost');
        });
    }
};
