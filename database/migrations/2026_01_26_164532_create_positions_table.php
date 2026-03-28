<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('positions', function (Blueprint $table) {
            $table->id('position_id'); // Primary Key
            $table->string('position_name');
            $table->text('pos_description')->nullable();

            // --- THE NEW LINK ---
            // This connects the Position to a specific Department using the custom PK 'department_id'
            $table->foreignId('department_id')
                  ->constrained('departments', 'department_id')
                  ->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};