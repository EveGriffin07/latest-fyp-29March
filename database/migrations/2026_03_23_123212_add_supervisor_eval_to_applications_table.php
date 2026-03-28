<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->integer('supervisor_score')->nullable()->after('interviewer_remarks');
            $table->text('supervisor_notes')->nullable()->after('supervisor_score');
            $table->string('supervisor_recommendation')->nullable()->after('supervisor_notes'); // e.g., 'Hire', 'Reject'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            //
        });
    }
};
