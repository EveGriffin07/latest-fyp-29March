<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // 1. Remove the old string column
            $table->dropColumn('department');

            // 2. Add the new unsignedBigInteger column for the ID
            // We use after('priority') to keep the table organized
            $table->unsignedBigInteger('department_id')->nullable()->after('priority');

            // 3. Set up the foreign key relationship
            $table->foreign('department_id')
                  ->references('department_id')
                  ->on('departments')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // Reverse the changes
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            $table->string('department')->nullable()->after('priority');
        });
    }
};