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
        Schema::create('pace_attempt_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pace_attempt_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->string('outcome');
            $table->text('reason');
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['pace_attempt_id', 'corrected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pace_attempt_corrections');
    }
};
