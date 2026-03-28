<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $primaryKey = 'training_id';

    // Add ALL columns that you want to save to the database here
    protected $fillable = [
        'training_name',
        'department_id',
        'tr_description',
        'start_date',
        'end_date',
        'start_time',
        'provider',
        'trainer_company',
        'trainer_email',
        'tr_status',
        'mode',
        'location',
        'max_participants',
        'qr_token',
        // --- NEW COLUMNS ---
        'approval_status',
        'budget',
        'purpose',
        'requested_by'
    ];

    // --- Relationships ---
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class, 'training_id');
    }

    // --- NEW: Relationship to the User who requested the training ---
    public function requester()
    {
        // 'requested_by' is the column we added to the database
        // 'user_id' is the primary key on your users table
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }
}