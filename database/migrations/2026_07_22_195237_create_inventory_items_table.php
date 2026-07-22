<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pace_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('item_type')->index();
            $table->string('sku')->unique();
            $table->unsignedInteger('reorder_level')->default(0);
            $table->boolean('is_consumable')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['pace_id', 'item_type']);
        });

        $now = now();
        DB::table('paces')->orderBy('id')->get(['id', 'number'])->chunk(500)->each(function ($paces) use ($now): void {
            DB::table('inventory_items')->insert($paces->map(fn ($pace) => [
                'pace_id' => $pace->id, 'item_type' => 'pace_booklet',
                'sku' => "PACE-{$pace->number}-{$pace->id}", 'reorder_level' => 0,
                'is_consumable' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ])->all());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
