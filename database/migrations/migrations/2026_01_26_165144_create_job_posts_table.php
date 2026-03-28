<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            // 1. Primary Key (following your naming convention)
            $table->id('job_id'); 

            // 2. Form Fields
            $table->string('job_title');
            $table->string('job_type');      // Full-time, Part-time, etc.
            $table->string('department');    // HR, Finance, etc.
            $table->string('location');      // Remote, On-site
            $table->string('salary_range');  // e.g., RM 3000 - RM 5000
            $table->text('job_description'); // Detailed text
            $table->text('requirements');    // Detailed text
            $table->date('closing_date');    // Date picker

            // 3. System Fields (Not in form, but needed for logic)
            $table->enum('job_status', ['Open', 'Closed', 'Draft'])->default('Open');
            $table->foreignId('posted_by')->constrained('users', 'user_id'); // Link to Admin
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};