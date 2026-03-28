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
        Schema::create('training_enrollments', function (Blueprint $table) {
    $table->id('enrollment_id');
    $table->foreignId('employee_id')->constrained('employees', 'employee_id');
    $table->foreignId('training_id')->constrained('training_programs', 'training_id');
    $table->date('enrollment_date');
    $table->enum('completion_status', ['enrolled', 'completed', 'failed']);
    $table->string('score_or_result')->nullable();
    $table->text('remarks')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
