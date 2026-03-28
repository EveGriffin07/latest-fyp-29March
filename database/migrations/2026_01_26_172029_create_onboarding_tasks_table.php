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
        Schema::create('onboarding_task', function (Blueprint $table) { // Note singular name in your ERD
    $table->id('task_id');
    $table->foreignId('onboarding_id')->constrained('onboarding', 'onboarding_id')->onDelete('cascade');
    
    $table->string('task_name');
    $table->text('task_description')->nullable();
    $table->boolean('is_completed')->default(false);
    $table->timestamp('completed_at')->nullable();
    $table->string('category')->nullable(); // e.g., "IT Setup", "HR Docs"
    $table->date('due_date')->nullable();
    
    // Links to user who completed/verified it
    $table->foreignId('user_id')->nullable()->constrained('users', 'user_id'); 
    $table->text('remarks')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_tasks');
    }
};
