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
        Schema::create('penalties', function (Blueprint $table) {
    $table->id('penalty_id');
    $table->foreignId('employee_id')->constrained('employees', 'employee_id');
    $table->foreignId('attendance_id')->nullable()->constrained('attendance', 'attendance_id');
    $table->string('penalty_name');
    $table->decimal('default_amount', 10, 2);
    $table->date('assigned_at');
    $table->date('removed_at')->nullable();
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};
