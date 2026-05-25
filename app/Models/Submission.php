<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'file_path',
        'original_filename',
        'grade',
        'feedback',
        'auto_graded',
        'duration_seconds',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'auto_graded'  => 'boolean',
    ];

    /**
     * Get the student who owns the submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task this submission belongs to.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the detailed rubric grade record for this submission.
     */
    public function submissionGrade()
    {
        return $this->hasOne(SubmissionGrade::class);
    }
}