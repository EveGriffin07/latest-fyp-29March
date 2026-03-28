<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustment_removal_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_line_item_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('supervisor_id')->nullable()->comment('users.user_id');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('users.user_id');
            $table->string('period_month', 7);
            $table->decimal('amount_snapshot', 12, 2);
            $table->string('reason_snapshot', 2000)->nullable();
            $table->string('sub_type_snapshot', 255)->nullable();
            $table->text('request_reason');
            $table->string('attachment_path', 500)->nullable();
            $table->text('employee_note')->nullable();
            $table->text('supervisor_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('status', 64)->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->timestamp('final_decision_at')->nullable();
            $table->timestamps();

            $table->foreign('payroll_line_item_id')
                ->references('id')
                ->on('payroll_line_items')
                ->nullOnDelete();
            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustment_removal_requests');
    }
};
