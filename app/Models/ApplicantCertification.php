<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantCertification extends Model
{
    protected $fillable = [
        'applicant_id', 
        'cert_name', 
        'issued_by', 
        'issue_date'
    ];

    public function profile()
    {
        // Explicitly matching the foreign and local keys just like experience
        return $this->belongsTo(ApplicantProfile::class, 'applicant_id', 'applicant_id');
    }
}