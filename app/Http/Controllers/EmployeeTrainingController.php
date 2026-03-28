<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\TrainingProgram;
use Carbon\Carbon;

class EmployeeTrainingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->first();

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

        $enrollments = TrainingEnrollment::where('employee_id', $employee->employee_id)
                        ->with('training') 
                        ->get();

        $today = Carbon::today();

        // 1. My Upcoming Trainings
        $upcoming = $enrollments->filter(function ($enrollment) use ($today) {
            $endDate = Carbon::parse($enrollment->training->end_date);
            return $enrollment->completion_status === 'enrolled' && $endDate->greaterThanOrEqualTo($today);
        });

        // 2. My Training History
        $history = $enrollments->filter(function ($enrollment) use ($today) {
            $endDate = Carbon::parse($enrollment->training->end_date);
            return in_array($enrollment->completion_status, ['completed', 'failed']) || $endDate->lessThan($today);
        });

        // === NEW: 3. Available Training Catalog ===
        // Get IDs of trainings the employee is already in
        $enrolledIds = $enrollments->pluck('training_id')->toArray();
        
        // Fetch approved trainings they haven't joined that haven't ended yet
        $availableTrainings = TrainingProgram::whereNotIn('training_id', $enrolledIds)
            ->where('approval_status', 'Approved') // Only approved ones
            ->where('end_date', '>=', $today)      // Hasn't ended yet
            ->orderBy('start_date', 'asc')
            ->get();

        return view('employee.training_my_plans', compact('upcoming', 'history', 'availableTrainings'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->firstOrFail();

        // 1. Load the training program (with enrollments to check capacity)
        $training = TrainingProgram::with('enrollments')->findOrFail($id);

        // 2. Check if the employee is already enrolled (returns null if they aren't)
        $enrollment = TrainingEnrollment::where('training_id', $id)
                        ->where('employee_id', $employee->employee_id)
                        ->first();

        return view('employee.training_show', compact('training', 'enrollment'));
    }

    // --- 1. UPDATED MULTI-DAY QR SCAN LOGIC ---
    public function scanQr($token)
    {
        $training = TrainingProgram::where('qr_token', $token)->first();

        if (!$training) {
            return redirect()->route('employee.training.index')->with('error', 'Invalid or expired QR code.');
        }

        $today = Carbon::today();
        $start = Carbon::parse($training->start_date);
        $end = Carbon::parse($training->end_date);

        // Security: Prevent scanning if training hasn't started or already ended
        if ($today->lt($start) || $today->gt($end)) {
            return redirect()->route('employee.training.index')
                ->with('error', 'This training is not active today. QR scanning is only allowed on active training days.');
        }

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->first();

        $enrollment = TrainingEnrollment::where('training_id', $training->training_id)
                        ->where('employee_id', $employee->employee_id)
                        ->first();

        if (!$enrollment) {
            return redirect()->route('employee.training.index')->with('error', 'Access Denied: You are not enrolled in this training.');
        }

        // Security: Prevent scanning multiple times on the same day
        if ($enrollment->last_scanned_date == $today->format('Y-m-d')) {
            return redirect()->route('employee.training.index')->with('success', 'You have already recorded your attendance for today!');
        }

        // Calculate progress
        $totalDays = $start->diffInDays($end) + 1;
        $newScanCount = $enrollment->scan_count + 1;
        $status = $enrollment->completion_status;

        // If they have scanned for every required day, mark as completed
        if ($newScanCount >= $totalDays) {
            $status = 'completed';
        }

        $enrollment->update([
            'scan_count'        => $newScanCount,
            'last_scanned_date' => $today->format('Y-m-d'),
            'completion_status' => $status,
            'remarks'           => "Attended $newScanCount out of $totalDays days."
        ]);

        return redirect()->route('employee.training.index')
                         ->with('success', "Attendance recorded for Day $newScanCount of $totalDays!");
    }

    // --- 2. NEW SELF-ENROLLMENT LOGIC ---
    public function selfEnroll(Request $request, $id)
    {
        $training = TrainingProgram::findOrFail($id);
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->firstOrFail();

        // Check if already enrolled
        $exists = TrainingEnrollment::where('training_id', $id)
                    ->where('employee_id', $employee->employee_id)->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'You are already enrolled in this training.');
        }

        // Check Capacity
        if ($training->mode == 'Onsite' && $training->max_participants) {
            $currentCount = TrainingEnrollment::where('training_id', $id)->count();
            if ($currentCount >= $training->max_participants) {
                return redirect()->back()->with('error', 'Sorry, this training has reached maximum capacity.');
            }
        }

        TrainingEnrollment::create([
            'training_id'       => $id,
            'employee_id'       => $employee->employee_id,
            'enrollment_date'   => now(),
            'completion_status' => 'enrolled',
            'scan_count'        => 0,
        ]);

        return redirect()->back()->with('success', 'You have successfully enrolled in ' . $training->training_name . '!');
    }
}