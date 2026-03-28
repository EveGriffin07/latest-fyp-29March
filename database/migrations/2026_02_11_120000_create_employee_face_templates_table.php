<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_face_templates')) {
            Schema::create('employee_face_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
                $table->json('embedding');
                $table->string('image_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'verified_method')) {
                $table->string('verified_method')->nullable()->after('at_status');
            }
            if (!Schema::hasColumn('attendance', 'verify_score')) {
                $table->float('verify_score')->nullable()->after('verified_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['verified_method', 'verify_score']);
        });

        Schema::dropIfExists('employee_face_templates');
    }
};
