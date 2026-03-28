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
        Schema::create('leave_requests', function (Blueprint $table) {
    $table->id('leave_request_id');
    $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
    $table->foreignId('leave_type_id')->constrained('leave_types', 'leave_type_id');
    $table->date('start_date');
    $table->date('end_date');
    $table->integer('total_days');
    $table->text('reason')->nullable();
    $table->enum('leave_status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id'); // Self-referencing or link to user
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
