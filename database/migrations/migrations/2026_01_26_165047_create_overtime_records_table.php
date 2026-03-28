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
        Schema::create('overtime_records', function (Blueprint $table) {
    $table->id('ot_id');
    $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
    $table->foreignId('period_id')->constrained('payroll_periods', 'period_id');
    $table->date('date');
    $table->decimal('hours', 5, 2);
    $table->decimal('rate_type', 5, 2)->default(1.5); // e.g., 1.5x hourly rate
    $table->string('reason')->nullable();
    $table->enum('ot_status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};
