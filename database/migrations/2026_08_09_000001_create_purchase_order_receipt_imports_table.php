<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_receipt_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->string('original_name');
            $table->string('file_path');
            $table->char('checksum', 64);
            $table->string('status')->index();
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->text('failure_reason')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('committed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('committed_at')->nullable();
            $table->foreignId('goods_receipt_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['purchase_order_id', 'status'], 'po_receipt_import_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipt_imports');
    }
};
