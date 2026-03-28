<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Add missing columns
            $table->string('resume_path')->nullable()->after('app_stage');
            $table->text('cover_letter')->nullable()->after('resume_path');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['resume_path', 'cover_letter']);
        });
    }
};