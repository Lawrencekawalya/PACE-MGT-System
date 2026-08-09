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
        Schema::create('score_key_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('learning_center_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->string('request_type');
            $table->unsignedInteger('quantity_requested');
            $table->string('status')->index();
            $table->text('request_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'inventory_item_id'], 'score_key_teacher_item_index');
            $table->index(['learning_center_id', 'requested_at'], 'score_key_center_requested_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('score_key_request_id')->nullable()->after('pace_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_to_user_id')->nullable()->after('score_key_request_id')->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_to_user_id');
            $table->dropConstrainedForeignId('score_key_request_id');
        });

        Schema::dropIfExists('score_key_requests');
    }
};
