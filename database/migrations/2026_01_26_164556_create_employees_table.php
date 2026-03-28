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
        Schema::create('employees', function (Blueprint $table) {
        $table->id('employee_id');
        
        // Foreign Keys (Must match the PK types of the parent tables)
        // Note: constrained('table_name', 'column_name')
        $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
        $table->foreignId('department_id')->constrained('departments', 'department_id');
        $table->foreignId('position_id')->constrained('positions', 'position_id');
        
        $table->string('employee_code')->unique();
        $table->enum('employee_status', ['active', 'inactive', 'terminated']);
        $table->date('hire_date');
        $table->decimal('base_salary', 10, 2); // Precision for currency
        $table->string('phone')->nullable();
        $table->text('address')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
