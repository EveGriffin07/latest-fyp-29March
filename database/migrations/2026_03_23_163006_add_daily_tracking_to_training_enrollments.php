<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_enrollments', function (Blueprint $table) {
            $table->date('last_scanned_date')->nullable()->after('completion_status');
            $table->integer('scan_count')->default(0)->after('last_scanned_date');
        });
    }

    public function down(): void
    {
        Schema::table('training_enrollments', function (Blueprint $table) {
            $table->dropColumn(['last_scanned_date', 'scan_count']);
        });
    }
};