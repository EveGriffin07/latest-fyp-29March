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
        Schema::create('kpi_templates', function (Blueprint $table) {
    $table->id('kpi_id');
    $table->string('kpi_title');
    $table->text('kpi_description')->nullable();
    $table->string('kpi_type'); // e.g., "Quantitative", "Qualitative"
    $table->decimal('default_target', 8, 2)->nullable();
    $table->decimal('weight', 5, 2)->default(0); // Percentage weight
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_templates');
    }
};
