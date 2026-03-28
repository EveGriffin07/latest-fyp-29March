<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $primaryKey = 'application_id';

    protected $fillable = [
        'job_id',
        'applicant_id',
        'resume_path',    // <--- Must be here
        'cover_letter',   // <--- Must be here
        'app_stage',
        'status',
        'test_score',
        'interview_score',
        'overall_score',
        'evaluation_notes',
        'interview_datetime', 
        'interview_location',
        'interviewer_id',
        'interviewer_status',
        'interviewer_remarks',
        // ... existing fields ...
        'interviewer_status',
        'interviewer_remarks',
        'supervisor_score',         // NEW
        'supervisor_notes',         // NEW
        'supervisor_recommendation' // NEW
    ];

    // 1. Link to the Job Post
    public function job()
    {
        return $this->belongsTo(JobPost::class, 'job_id', 'job_id');
    }

    // 2. Link to the Applicant's Profile
    public function applicant()
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_id', 'applicant_id');
    }
    
    // Fallback: Link to User directly
    public function user()
    {
        return $this->belongsTo(User::class, 'applicant_id', 'user_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id', 'user_id');
    }
}