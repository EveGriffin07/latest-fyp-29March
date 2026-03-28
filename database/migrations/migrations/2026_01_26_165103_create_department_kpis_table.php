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
        Schema::create('department_kpis', function (Blueprint $table) {
    $table->id('dept_kpi_id');
    $table->foreignId('department_id')->constrained('departments', 'department_id');
    $table->foreignId('kpi_id')->constrained('kpi_templates', 'kpi_id');
    $table->date('period_start');
    $table->date('period_end');
    $table->date('deadline');
    $table->decimal('target', 8, 2);
    $table->decimal('progress', 8, 2)->default(0);
    $table->enum('status', ['active', 'completed', 'failed']);
    $table->text('notes')->nullable();
    $table->foreignId('user_id')->constrained('users', 'user_id'); // Created/Managed by
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_kpis');
    }
};
