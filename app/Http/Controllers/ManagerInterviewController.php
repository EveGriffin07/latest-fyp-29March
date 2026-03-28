<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use App\Models\Notification; // Ensure you have your Notification model imported

class ManagerInterviewController extends Controller
{

    // 1. Show all interviews assigned to the logged-in Manager
    public function index()
    {
        $userId = Auth::id();

        // Fetch applications WITH all the nested profile data!
        $interviews = Application::with([
            'job',
            'interviewer',             // <--- Knows who is assigned
            'applicant.user',          // <--- Fetches applicant's basic user account
            'applicant.skills',        // <--- Fetches skills for the modal
            'applicant.experiences',   // <--- Fetches experience for the modal
            'applicant.educations',    // <--- Fetches education for the modal
            'applicant.languages'      // <--- Fetches languages (if you use them)
        ])
            ->where('interviewer_id', $userId) // <--- Keeps it restricted to THIS supervisor
            ->where('app_stage', 'Interview')
            ->orderBy('interview_datetime', 'asc') 
            ->get();

        return view('supervisor.interviews', compact('interviews'));
    }

    // 2. Accept the Interview Schedule
    public function accept(Request $request, $id)
    {
        $application = Application::where('interviewer_id', Auth::id())->findOrFail($id);

        $application->interviewer_status = 'Accepted';
        $application->interviewer_remarks = $request->remarks ?? 'I will be there.';
        $application->save();

        // Notify HR that the manager accepted
        Notification::create([
            'user_id' => 1, // Change this to your HR Admin's user_id or loop through admin roles
            'title'   => 'Interview Accepted',
            'message' => Auth::user()->name . ' accepted the interview schedule for ' . $application->applicant->full_name . '.',
            'type'    => 'interview_update',
            'link'    => route('admin.applicants.show', $application->application_id),
            'is_read' => false,
        ]);

        return redirect()->back()->with('success', 'Interview schedule accepted successfully!');
    }

    // 3. Reject the Interview Schedule (Requires Remarks)
    public function reject(Request $request, $id)
    {
        $application = Application::where('interviewer_id', Auth::id())->findOrFail($id);

        // Even though remarks are optional from the UI, we save whatever they type
        $application->interviewer_status = 'Rejected';
        $application->interviewer_remarks = $request->remarks ?? 'I am unavailable at this time. Please reschedule.';
        $application->save();

        // Notify HR that they need to reschedule
        Notification::create([
            'user_id' => 1, // Change this to your HR Admin's user_id
            'title'   => 'Interview Rejected - Reschedule Needed',
            'message' => Auth::user()->name . ' rejected the interview for ' . $application->applicant->full_name . '. Reason: ' . $application->interviewer_remarks,
            'type'    => 'interview_update',
            'link'    => route('admin.applicants.show', $application->application_id),
            'is_read' => false,
        ]);

        return redirect()->back()->with('error', 'Interview rejected. HR has been notified to reschedule.');
    }

    // 4. Submit Supervisor Evaluation
    public function evaluate(Request $request, $id)
    {
        $application = Application::where('interviewer_id', Auth::id())->findOrFail($id);

        $request->validate([
            'supervisor_score' => 'required|numeric|min:0|max:100',
            'supervisor_notes' => 'required|string|min:10',
            'supervisor_recommendation' => 'required|string|in:Hire,Reject,Shortlist'
        ]);

        $application->update([
            'supervisor_score' => $request->supervisor_score,
            'supervisor_notes' => $request->supervisor_notes,
            'supervisor_recommendation' => $request->supervisor_recommendation,
            'interviewer_status' => 'Evaluated' // Update status so it shows as completed!
        ]);

        // Notify HR that the technical evaluation is ready
        Notification::create([
            'user_id' => 1, // Change to your HR Admin's user_id
            'title'   => 'Technical Evaluation Submitted',
            'message' => Auth::user()->name . ' has submitted their technical interview feedback for ' . $application->applicant->full_name . '.',
            'type'    => 'evaluation_submitted',
            'link'    => route('admin.applicants.show', $application->application_id),
            'is_read' => false,
        ]);

        return redirect()->back()->with('success', 'Evaluation submitted successfully! HR has been notified.');
    }
}