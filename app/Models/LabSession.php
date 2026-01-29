<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabSession extends Model
{
    use HasFactory;

   protected $fillable = [
    'class_code', 
    'subject_name', 
    'schedule_day',   // Add this
    'schedule_time',  // Add this
    'faculty_id', 
    'is_active'
];

    // The Professor/Admin who created the session
    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    // The Students joined in this session
    public function students()
    {
        return $this->belongsToMany(User::class, 'class_student', 'lab_session_id', 'user_id')
                    ->withPivot('is_present');
    }
}