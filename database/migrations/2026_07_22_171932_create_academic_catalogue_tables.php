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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_closed')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_closed')->default(false)->index();
            $table->timestamps();
            $table->unique(['academic_year_id', 'name']);
            $table->unique(['academic_year_id', 'sort_order']);
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('sort_order')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('edition')->default('');
            $table->boolean('is_pace_course')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['subject_id', 'name', 'edition']);
        });

        Schema::create('paces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('number');
            $table->string('title')->nullable();
            $table->string('edition')->default('');
            $table->unsignedInteger('sequence_order');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['course_id', 'number', 'edition']);
            $table->unique(['course_id', 'sequence_order', 'edition']);
        });

        Schema::create('curriculum_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['level_id', 'course_id']);
            $table->unique(['level_id', 'sort_order']);
        });

        Schema::create('curriculum_requirement_pace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pace_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence_order');
            $table->timestamps();
            $table->unique(['curriculum_requirement_id', 'pace_id'], 'curriculum_req_pace_unique');
            $table->unique(['curriculum_requirement_id', 'sequence_order'], 'curriculum_req_sequence_unique');
        });

        Schema::create('catalogue_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('file_path');
            $table->string('checksum', 64)->index();
            $table->string('status')->default('uploaded')->index();
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('created_records')->default(0);
            $table->unsignedInteger('updated_records')->default(0);
            $table->unsignedInteger('skipped_records')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('committed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('committed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('catalogue_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogue_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->string('status')->default('valid')->index();
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->unique(['catalogue_import_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogue_import_rows');
        Schema::dropIfExists('catalogue_imports');
        Schema::dropIfExists('curriculum_requirement_pace');
        Schema::dropIfExists('curriculum_requirements');
        Schema::dropIfExists('paces');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
    }
};
