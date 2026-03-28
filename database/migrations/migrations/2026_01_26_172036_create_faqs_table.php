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
        Schema::create('faqs', function (Blueprint $table) {
    $table->id('kb_id');
    $table->string('module_scope'); // e.g., "Payroll", "Leave"
    $table->text('question');
    $table->text('answer');
    $table->string('keywords')->nullable();
    $table->enum('status', ['published', 'draft', 'archived']);
    $table->foreignId('created_by')->constrained('users', 'user_id');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
