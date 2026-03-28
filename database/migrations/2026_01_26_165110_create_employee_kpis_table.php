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
        Schema::create('employee_kpis', function (Blueprint $table) {
    $table->id('emp_kpi_id');
    $table->foreignId('employee_id')->constrained('employees', 'employee_id');
    $table->foreignId('kpi_id')->constrained('kpi_templates', 'kpi_id');
    $table->foreignId('dept_kpi_id')->nullable()->constrained('department_kpis', 'dept_kpi_id');
    $table->date('assigned_date');
    $table->date('deadline');
    $table->decimal('actual_score', 8, 2)->nullable();
    $table->string('rating')->nullable(); // e.g., "A", "B", "C"
    $table->text('comments')->nullable();
    $table->enum('kpi_status', ['pending', 'in_progress', 'completed']);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_kpis');
    }
};
