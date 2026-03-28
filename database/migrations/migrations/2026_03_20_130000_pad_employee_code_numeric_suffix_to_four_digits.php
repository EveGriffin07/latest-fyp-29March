<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize EMP-{n} codes to EMP-{nnnn} (e.g. EMP-38 → EMP-0038) when no collision.
     */
    public function up(): void
    {
        foreach (Employee::query()->orderBy('employee_id')->cursor() as $employee) {
            $code = (string) ($employee->employee_code ?? '');
            if (! preg_match('/^EMP-(\d+)$/', $code, $m)) {
                continue;
            }
            $padded = 'EMP-'.str_pad((string) ((int) $m[1]), 4, '0', STR_PAD_LEFT);
            if ($padded === $code) {
                continue;
            }
            $taken = Employee::query()
                ->where('employee_code', $padded)
                ->where('employee_id', '!=', $employee->employee_id)
                ->exists();
            if ($taken) {
                continue;
            }
            DB::table('employees')->where('employee_id', $employee->employee_id)->update(['employee_code' => $padded]);
        }
    }

    public function down(): void
    {
        // Irreversible without storing previous codes
    }
};
