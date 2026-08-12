<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('category');
            $table->string('priority');
            $table->string('status')->default('active');
            $table->unsignedInteger('affected_count')->default(0);
            $table->string('fingerprint', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('notification_sequence')->default(0);
            $table->timestamp('first_detected_at');
            $table->timestamp('last_detected_at');
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('last_notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_alerts');
    }
};
