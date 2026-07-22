<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->foreignId('teacher_id')->nullable()->after('id')->constrained('users')->restrictOnDelete();
            $table->foreignId('registered_by')->nullable()->after('teacher_id')->constrained('users')->nullOnDelete();
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropIndex(['teacher_id', 'status']);
            $table->dropConstrainedForeignId('registered_by');
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
