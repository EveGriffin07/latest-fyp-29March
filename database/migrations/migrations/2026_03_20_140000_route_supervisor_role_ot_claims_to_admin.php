<?php

use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supervisor-role users' OT claims should not sit in SUBMITTED_TO_SUPERVISOR; send to admin queue.
     */
    public function up(): void
    {
        $submitted = OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR;
        $adminPending = OvertimeClaim::STATUS_ADMIN_PENDING;

        $rows = DB::table('overtime_claims as oc')
            ->join('employees as e', 'e.employee_id', '=', 'oc.employee_id')
            ->join('users as u', 'u.user_id', '=', 'e.user_id')
            ->where('oc.status', $submitted)
            ->whereRaw('LOWER(TRIM(COALESCE(u.role, \'\'))) = ?', ['supervisor'])
            ->select(['oc.id', 'oc.overtime_record_id', 'e.user_id as claimant_user_id'])
            ->get();

        $now = now();
        foreach ($rows as $row) {
            DB::table('overtime_claims')->where('id', $row->id)->update([
                'status' => $adminPending,
                'supervisor_id' => $row->claimant_user_id,
            ]);
            if ($row->overtime_record_id && Schema::hasTable('overtime_records')) {
                DB::table('overtime_records')->where('ot_id', $row->overtime_record_id)->update([
                    'final_status' => OvertimeRecord::FINAL_PENDING_ADMIN,
                    'submitted_to_admin_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Not safely reversible without storing previous status.
    }
};
