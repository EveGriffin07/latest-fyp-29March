<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrainingProgram;
use App\Models\TrainingEnrollment;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Str;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    // 1. Index (Main Page)
    public function index()
    {
        // Auto-update status (ONLY FOR APPROVED PROGRAMS)
        TrainingProgram::where('end_date', '<', Carbon::today())
            ->where('approval_status', 'Approved') // <--- Prevent it from touching Pending requests
            ->where('tr_status', '!=', 'completed')
            ->update(['tr_status' => 'completed']);

        TrainingProgram::whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->where('approval_status', 'Approved') // <--- Prevent it from touching Pending requests
            ->where('tr_status', '!=', 'active')
            ->update(['tr_status' => 'active']);

        // Fetch Approved Programs for the main table
        $programs = TrainingProgram::with('department')
            ->where('approval_status', 'Approved')
            ->orderBy('start_date', 'desc')
            ->get();
            
        $departments = Department::all();
        
        // Fetch requests waiting for Admin approval
        $pendingRequests = TrainingProgram::with(['department', 'requester'])
            ->where('approval_status', 'Pending')
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate metrics only for Approved programs
        $total = $programs->count();
        $ongoing = $programs->where('tr_status', 'active')->count();
        $completed = $programs->where('tr_status', 'completed')->count();
        $upcoming = $programs->where('tr_status', 'planned')->count();

        return view('admin.training_admin', compact('programs', 'departments', 'total', 'ongoing', 'completed', 'upcoming', 'pendingRequests'));
    }

    // 2. Store (Create New Training)
    public function store(Request $request)
    {
        $request->validate([
            'trainingTitle'   => 'required|string|max:255',
            'trainerName'     => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.\,\-]+$/'],
            'trainerCompany'  => 'nullable|string|max:255',
            'trainerEmail'    => 'nullable|email|max:255',
            'department'      => 'nullable|string', 
            'startDate'       => 'required|date|after_or_equal:today',
            'startTime'       => 'required', 
            'endDate'         => 'required|date|after_or_equal:startDate', 
            'endTime'         => 'required', // <--- NEW
            'mode'            => 'required|in:Onsite,Online',
            'maxParticipants' => 'nullable|integer|min:1|required_if:mode,Onsite', 
            'location'        => 'required|string|max:255',
            'description'     => 'nullable|string',
        ], [
            'trainerName.regex' => 'The Trainer Name can only contain letters, spaces, dots, and hyphens.',
            'startDate.after_or_equal' => 'The Start Date must be today or a future date.',
        ]);

        // --- STRICT TIME VALIDATION ---
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i');

        // 1. If starts today, Start Time cannot be in the past
        if ($request->startDate == $today && $request->startTime < $nowTime) {
            return back()->withErrors(['startTime' => 'The Start Time cannot be in the past.'])->withInput();
        }
        // 2. If it starts and ends on the same day, End Time must be strictly after Start Time
        if ($request->startDate == $request->endDate && $request->endTime <= $request->startTime) {
            return back()->withErrors(['endTime' => 'The End Time must be later than the Start Time.'])->withInput();
        }

        $deptId = null;
        if ($request->department) {
            $dept = Department::where('department_name', $request->department)->first();
            $deptId = $dept ? $dept->department_id : null;
        }

        $start = Carbon::parse($request->startDate);
        $end   = Carbon::parse($request->endDate);

        $status = 'planned';
        if (Carbon::today()->between($start, $end)) {
            $status = 'active';
        } elseif (Carbon::today()->gt($end)) {
            $status = 'completed';
        }

        TrainingProgram::create([
            'training_name'    => $request->trainingTitle,
            'provider'         => $request->trainerName,
            'trainer_company'  => $request->trainerCompany,
            'trainer_email'    => $request->trainerEmail, 
            'department_id'    => $deptId,
            'start_date'       => $request->startDate,
            'start_time'       => $request->startTime, 
            'end_date'         => $request->endDate,
            'end_time'         => $request->endTime, // <--- NEW
            'mode'             => $request->mode,
            'max_participants' => $request->mode == 'Online' ? null : $request->maxParticipants, 
            'location'         => $request->location,
            'tr_description'   => $request->description,
            'tr_status'        => $status,
            'approval_status'  => 'Approved', // Ensure Admins instantly approve their own creations
            'qr_token'         => Str::random(40), 
        ]);

        return redirect()->route('admin.training')->with('success', 'Training program created successfully!');
    }

    // 3. Update (Edit Existing Training)
    public function update(Request $request, $id)
    {
        $program = TrainingProgram::findOrFail($id);

        $request->validate([
            'trainingTitle'   => 'required|string|max:255',
            'trainerName'     => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.\,\-]+$/'],
            'trainerCompany'  => 'nullable|string|max:255', 
            'trainerEmail'    => 'nullable|email|max:255',  
            'startDate'       => 'required|date',
            'startTime'       => 'required', 
            'endDate'         => 'required|date|after_or_equal:startDate',
            'endTime'         => 'required', // <--- NEW
            'mode'            => 'required|in:Onsite,Online',
            'maxParticipants' => 'nullable|integer|min:1|required_if:mode,Onsite', 
            'location'        => 'required|string|max:255',
        ]);

        // --- STRICT TIME VALIDATION (Same rules for updates) ---
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i');

        if ($request->startDate == $today && $request->startTime < $nowTime) {
            return back()->withErrors(['startTime' => 'The Start Time cannot be in the past.'])->withInput();
        }
        if ($request->startDate == $request->endDate && $request->endTime <= $request->startTime) {
            return back()->withErrors(['endTime' => 'The End Time must be later than the Start Time.'])->withInput();
        }

        $deptId = null;
        if ($request->department) {
            $dept = Department::where('department_name', $request->department)->first();
            $deptId = $dept ? $dept->department_id : null;
        }
        
        $start = Carbon::parse($request->startDate);
        $end   = Carbon::parse($request->endDate);
        
        $status = 'planned';
        if (Carbon::today()->between($start, $end)) $status = 'active';
        elseif (Carbon::today()->gt($end)) $status = 'completed';

        $program->update([
            'training_name'    => $request->trainingTitle,
            'provider'         => $request->trainerName,
            'trainer_company'  => $request->trainerCompany,
            'trainer_email'    => $request->trainerEmail,  
            'department_id'    => $deptId,
            'start_date'       => $request->startDate,
            'start_time'       => $request->startTime, 
            'end_date'         => $request->endDate,
            'end_time'         => $request->endTime, // <--- NEW
            'mode'             => $request->mode,
            'max_participants' => $request->mode == 'Online' ? null : $request->maxParticipants, 
            'location'         => $request->location,
            'tr_description'   => $request->description,
            'tr_status'        => $status,
        ]);

        return redirect()->back()->with('success', 'Training program updated successfully!');
    }


    // 4. Delete
    public function destroy($id)
    {
        $program = TrainingProgram::findOrFail($id);
        $program->enrollments()->delete();
        $program->delete();

        return redirect()->route('admin.training')->with('success', 'Training program deleted successfully.');
    }

    // 5. Show Details
    public function show($id)
    {
        $program = TrainingProgram::with(['enrollments.employee.user', 'department'])
            ->findOrFail($id);

        $enrolledIds = $program->enrollments->pluck('employee_id')->toArray();
        
        $potentialTrainees = Employee::with(['user', 'department'])
            ->whereNotIn('employee_id', $enrolledIds)
            ->where('employee_status', 'active')
            ->get();

        $departments = Department::all();

        return view('admin.training_show', compact('program', 'potentialTrainees', 'departments'));
    }

    // 6. Store Enrollment (BULK)
    public function storeEnrollment(Request $request, $id)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,employee_id',
        ]);

        // Capacity check
        $program = TrainingProgram::findOrFail($id);
        $currentEnrollmentCount = $program->enrollments()->count();
        $attemptedEnrollmentCount = count($request->employee_ids);

        if ($program->mode == 'Onsite' && $program->max_participants) {
            if (($currentEnrollmentCount + $attemptedEnrollmentCount) > $program->max_participants) {
                $availableSlots = $program->max_participants - $currentEnrollmentCount;
                return redirect()->back()->with('error', "Cannot enroll. Only $availableSlots slot(s) remaining for this onsite training.");
            }
        }

        $count = 0;
        foreach ($request->employee_ids as $empId) {
            $exists = TrainingEnrollment::where('training_id', $id)
                        ->where('employee_id', $empId)->exists();
            
            if (!$exists) {
                TrainingEnrollment::create([
                    'training_id'       => $id,
                    'employee_id'       => $empId,
                    'enrollment_date'   => now(),
                    'completion_status' => 'enrolled',
                ]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "$count employees enrolled successfully!");
    }

    // 7. Update Status
    public function updateEnrollmentStatus(Request $request, $id)
    {
        $enrollment = TrainingEnrollment::findOrFail($id);
        $enrollment->update([
            'completion_status' => $request->completion_status,
            'remarks'           => $request->remarks
        ]);
        return redirect()->back()->with('success', 'Participant status updated.');
    }

    // API for Calendar
    public function getEvents()
    {
        $programs = TrainingProgram::all();
        $events = [];
        foreach ($programs as $prog) {
            $events[] = [
                'title' => $prog->training_name,
                'start' => $prog->start_date,
                'end'   => Carbon::parse($prog->end_date)->addDay()->format('Y-m-d'),
                'url'   => route('admin.training.show', $prog->training_id),
                'backgroundColor' => $prog->tr_status == 'completed' ? '#10b981' : ($prog->tr_status == 'active' ? '#3b82f6' : '#f97316'),
            ];
        }
        return response()->json($events);
    }

    // --- NEW: Supervisor Submits a Request ---
    public function storeSupervisorRequest(Request $request)
    {
        $request->validate([
            'trainingTitle' => 'required|string|max:255',
            'department'    => 'required|string', // <--- Added validation
            'purpose'       => 'required|string',
            'budget'        => 'nullable|numeric|min:0',
            'startDate'     => 'required|date|after_or_equal:today',
            'endDate'       => 'nullable|date|after_or_equal:startDate',
            'mode'          => 'required|in:Onsite,Online',
        ], [
            'endDate.after_or_equal' => 'The End Date cannot be earlier than the Start Date.',
        ]);

        // Find the Department ID based on the name they selected
        $deptId = null;
        if ($request->department) {
            $dept = Department::where('department_name', $request->department)->first();
            $deptId = $dept ? $dept->department_id : null;
        }

        TrainingProgram::create([
            'training_name'   => $request->trainingTitle,
            'department_id'   => $deptId, // <--- Save the Department ID
            'purpose'         => $request->purpose,
            'budget'          => $request->budget,
            'start_date'      => $request->startDate,
            'end_date'        => $request->endDate ?? $request->startDate,
            'mode'            => $request->mode,
            'tr_status'       => 'planned',
            'approval_status' => 'Pending',
            'requested_by'    => Auth::id(), // <--- This saves WHO requested it
            'qr_token'        => Str::random(40),
        ]);

        Notification::create([
            'user_id' => 1, 
            'title'   => 'New Training Request',
            'message' => Auth::user()->name . ' requested a new training for the ' . $request->department . ' department.',
            'type'    => 'training_request',
            'link'    => route('admin.training'),
            'is_read' => false,
        ]);

        return redirect()->back()->with('success', 'Training request submitted to HR for approval.');
    }

    // --- NEW: Admin Approves the Request ---
    public function approveRequest($id)
    {
        $program = TrainingProgram::findOrFail($id);
        $program->update(['approval_status' => 'Approved']);
        return redirect()->back()->with('success', 'Training request approved! It is now on the official calendar.');
    }

    // --- NEW: Admin Rejects the Request ---
    public function rejectRequest($id)
    {
        $program = TrainingProgram::findOrFail($id);
        $program->update(['approval_status' => 'Rejected']);
        // Alternatively, use $program->delete(); if you don't want to keep a history of rejected requests.
        return redirect()->back()->with('success', 'Training request rejected.');
    }
}