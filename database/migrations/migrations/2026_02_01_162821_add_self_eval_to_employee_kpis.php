<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_kpis', function (Blueprint $table) {
            // New columns for Employee's input
            $table->text('employee_comments')->nullable()->after('comments');
            $table->integer('self_rating')->nullable()->after('actual_score'); 
        });
    }

    public function down(): void
    {
        Schema::table('employee_kpis', function (Blueprint $table) {
            $table->dropColumn(['employee_comments', 'self_rating']);
        });
    }
};