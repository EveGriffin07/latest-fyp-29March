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
    Schema::create('applicant_profiles', function (Blueprint $table) {
    $table->id('applicant_id');
    $table->string('full_name');
    $table->string('phone');
    $table->string('location')->nullable();
    $table->string('avatar_path')->nullable();
    $table->string('resume_path')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_profiles');
    }
};
