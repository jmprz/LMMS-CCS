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
    // 1. Manual Override: If the Professor explicitly started the session, it's LIVE.
    if ($this->is_active) {
        return true;
    }

    $now = now(); 

    // 2. Day Check (e.g., "Monday")
    // Ensure the database value matches the case (e.g., 'Monday' vs 'monday')
    if (strcasecmp($now->format('l'), $this->schedule_day) !== 0) {
        return false;
    }

    // 3. Time Range Check
    if (str_contains($this->schedule_time, '-')) {
        try {
            [$startStr, $endStr] = explode('-', $this->schedule_time);
            
            // Set the Carbon objects to today so 'between' works correctly
            $startTime = \Carbon\Carbon::createFromFormat('g:i A', trim($startStr), $now->timezone);
            $endTime = \Carbon\Carbon::createFromFormat('g:i A', trim($endStr), $now->timezone);

            return $now->between($startTime, $endTime);
        } catch (\Exception $e) {
            // If the time format in DB is messy, fail gracefully
            return false;
        }
    }

    return false;
}
}