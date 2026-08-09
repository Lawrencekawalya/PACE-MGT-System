<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_receipt_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_receipt_import_id');
            $table->unsignedInteger('row_number');
            $table->foreignId('purchase_order_line_id')->nullable();
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->string('status')->index();
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->foreign(
                'purchase_order_receipt_import_id',
                'po_receipt_rows_import_fk',
            )->references('id')->on('purchase_order_receipt_imports')->cascadeOnDelete();
            $table->foreign(
                'purchase_order_line_id',
                'po_receipt_rows_order_line_fk',
            )->references('id')->on('purchase_order_lines')->restrictOnDelete();
            $table->unique(['purchase_order_receipt_import_id', 'row_number'], 'po_receipt_import_row_unique');
            $table->index(
                ['purchase_order_receipt_import_id', 'purchase_order_line_id'],
                'po_receipt_import_line_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipt_import_rows');
    }
};
