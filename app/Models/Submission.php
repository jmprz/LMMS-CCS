<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    // Allow mass assignment for these fields
    protected $fillable = [
        'task_id',
        'user_id',
        'file_path',
        'original_filename',
        'grade',
        'feedback',
        'duration_seconds',
        'submitted_at'
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
}