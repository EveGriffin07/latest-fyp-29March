<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('attendance', function (Blueprint $table) {
        $table->id('attendance_id');
        $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
        
        $table->date('date');
        $table->time('clock_in_time')->nullable();
        $table->time('clock_out_time')->nullable();
        $table->enum('at_status', ['present', 'absent', 'late', 'leave']);
        $table->integer('late_minutes')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
