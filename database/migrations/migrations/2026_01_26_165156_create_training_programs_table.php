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
        Schema::create('training_programs', function (Blueprint $table) {
    $table->id('training_id');
    $table->foreignId('department_id')->nullable()->constrained('departments', 'department_id');
    $table->string('training_name');
    $table->text('tr_description')->nullable();
    $table->date('start_date');
    $table->date('end_date');
    $table->string('provider')->nullable();
    $table->enum('tr_status', ['planned', 'active', 'completed']);
    $table->string('mode'); // Online/Offline
    $table->string('location')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};
