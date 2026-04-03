<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 
        'lab_session_id', 
        'attendance_date', 
        'joined_at', 
        'status'
    ];

    // Relationship to the Student
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to the Subject/Session
    public function labSession()
    {
        return $this->belongsTo(LabSession::class);
    }
}
