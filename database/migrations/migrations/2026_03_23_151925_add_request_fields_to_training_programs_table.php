<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            // New fields for Supervisor Requests
            $table->decimal('budget', 10, 2)->nullable()->after('location');
            $table->text('purpose')->nullable()->after('budget');
            
            // "Approved" by default so old data isn't affected. New requests will be "Pending".
            $table->string('approval_status')->default('Approved')->after('purpose'); 
            
            // Track who requested it
            $table->unsignedBigInteger('requested_by')->nullable()->after('approval_status');
            $table->foreign('requested_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['budget', 'purpose', 'approval_status', 'requested_by']);
        });
    }
};