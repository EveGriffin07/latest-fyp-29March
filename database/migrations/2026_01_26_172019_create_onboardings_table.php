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
        Schema::create('onboarding', function (Blueprint $table) {
    $table->id('onboarding_id');
    $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
    $table->foreignId('assigned_by')->nullable()->constrained('users', 'user_id'); // Who assigned this?
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->enum('status', ['pending', 'in_progress', 'completed']);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboardings');
    }
};
