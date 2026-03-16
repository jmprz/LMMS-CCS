<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivity extends Model
{
    protected $fillable = [
        'user_id', 
        'subject_id', 
        'activity_name', 
        'started_at', 
        'ended_at',
        'duration_seconds', // Add this
        'is_completed'      // Add this
    ];

    // Helper to format timestamps
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
