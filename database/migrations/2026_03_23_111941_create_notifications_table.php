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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // Assuming your users table primary key is user_id. If it is 'id', change this to match!
            $table->unsignedBigInteger('user_id'); 
            
            $table->string('title');
            $table->text('message');
            $table->string('type')->nullable(); // e.g., 'interview_invite'
            $table->string('link')->nullable(); // The URL they should click
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Link it to the user who receives it
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
