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
        Schema::create('applications', function (Blueprint $table) {
    $table->id('application_id');
    $table->foreignId('applicant_id')->constrained('applicant_profiles', 'applicant_id');
    $table->foreignId('job_id')->constrained('job_posts', 'job_id');
    $table->string('app_stage'); // e.g., "Interview", "Screening"
    $table->decimal('test_score', 5, 2)->nullable();
    $table->decimal('interview_score', 5, 2)->nullable();
    $table->decimal('overall_score', 5, 2)->nullable();
    $table->text('evaluation_notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
