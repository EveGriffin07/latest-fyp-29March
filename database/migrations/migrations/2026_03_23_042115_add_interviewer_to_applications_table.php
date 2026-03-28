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
            $table->unsignedBigInteger('interviewer_id')->nullable()->after('interview_location');
            $table->string('interviewer_status')->nullable()->default('Pending')->after('interviewer_id');
            $table->text('interviewer_remarks')->nullable()->after('interviewer_status');

            // Assuming your users/employees table is named 'users' and primary key is 'user_id'
            $table->foreign('interviewer_id')->references('user_id')->on('users')->onDelete('set null');
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
