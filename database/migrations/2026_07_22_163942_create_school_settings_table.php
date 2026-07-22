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
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('official_name')->default('Friends International Christian Academy');
            $table->string('short_name')->default('FICA');
            $table->string('slogan')->nullable()->default('#1 ACE Mission School in Uganda');
            $table->char('country_code', 2)->default('UG');
            $table->string('timezone')->default('Africa/Kampala');
            $table->string('date_format')->default('DD/MM/YYYY');
            $table->string('time_format')->default('12-hour');
            $table->string('logo_path')->nullable();
            $table->decimal('self_test_pass_mark', 5, 2)->default(80);
            $table->unsignedSmallInteger('self_test_retry_limit')->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
