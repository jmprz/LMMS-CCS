<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LabSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_code', 
        'subject_name', 
        'schedule_day',  
        'schedule_time',  
        'program',
        'year_level',
        'section',
        'faculty_id', 
        'semester',
        'school_year',
        'is_active'
    ];

    /**
     * The Professor/Admin who created the session.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    /**
     * The Students joined in this session.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_student', 'lab_session_id', 'user_id')
                    ->withPivot('is_present');
    }

    /**
     * Accessor for currently active/present students.
     */
    public function getActiveStudentsAttribute()
{
    return $this->students()
        ->wherePivot('is_present', true)
        // Check the pivot table's timestamp instead of the user's
        ->wherePivot('updated_at', '>=', now()->subMinutes(1)) 
        ->get();
}

    /**
     * Tasks associated with this session.
     * Uses 'subject_id' as the foreign key in the 'tasks' table.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'subject_id');
    }

    /**
     * Quizzes associated with this session.
     * Uses 'subject_id' as the foreign key in the 'quizzes' table.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'subject_id');
    }

    // App\Models\LabSession.php (or Classroom.php)

public function materials()
{
    return $this->hasMany(Material::class);
}

public function isCurrentlyScheduled()
{
    $now = now(); // Gets current date and time
    
    // 1. Check if today matches the schedule_day (e.g., "Monday")
    if ($now->format('l') !== $this->schedule_day) {
        return false;
    }

    // 2. Parse the schedule_time string (e.g., "8:00 AM - 10:00 AM")
    if (str_contains($this->schedule_time, '-')) {
        [$startStr, $endStr] = explode('-', $this->schedule_time);
        
        // Convert strings to carbon objects for today
        $startTime = \Carbon\Carbon::createFromFormat('g:i A', trim($startStr));
        $endTime = \Carbon\Carbon::createFromFormat('g:i A', trim($endStr));

        // 3. Check if current time is between start and end
        return $now->between($startTime, $endTime);
    }

    return false;
}
}