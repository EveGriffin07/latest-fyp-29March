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
        Schema::create('payslips', function (Blueprint $table) {
    $table->id('payslip_id');
    $table->foreignId('employee_id')->constrained('employees', 'employee_id');
    $table->foreignId('period_id')->constrained('payroll_periods', 'period_id');
    $table->decimal('basic_salary', 12, 2);
    $table->decimal('total_allowances', 12, 2)->default(0);
    $table->decimal('total_deductions', 12, 2)->default(0);
    $table->decimal('total_overtime_amount', 12, 2)->default(0);
    $table->decimal('net_salary', 12, 2);
    $table->timestamp('generated_at')->useCurrent();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
