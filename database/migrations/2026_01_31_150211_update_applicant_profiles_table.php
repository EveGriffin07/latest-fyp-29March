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
        Schema::table('applicant_profiles', function (Blueprint $table) {
            // 1. Add the 'email' column (nullable just in case)
            // Placing it after 'full_name' keeps your database tidy.
            $table->string('email')->nullable()->after('full_name');

            // 2. Fix the "Field phone doesn't have a default value" error
            // We change the existing 'phone' column to allow NULL values.
            $table->string('phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            // Remove the email column if we roll back
            $table->dropColumn('email');

            // OPTIONAL: Revert phone to be mandatory (Not Null)
            // We usually leave this alone to avoid errors, but here is the code:
            // $table->string('phone')->nullable(false)->change();
        });
    }
};