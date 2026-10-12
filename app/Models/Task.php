<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'subject_id',
        'learning_topic_id',
        'title',
        'description',
        'deadline',
        'points',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    /**
     * Get the lab session this task belongs to.
     */
    public function learningTopic(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LearningTopic::class);
    }

    public function labSession()
    {
        return $this->belongsTo(LabSession::class, 'subject_id');
    }

    /**
     * Get all submissions for this task.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get current user's submission for this task.
     */
    public function currentUserSubmission()
    {
        return $this->hasOne(Submission::class)->where('user_id', auth()->id());
    }

    /**
     * Get the rubric for this task.
     */
    public function rubric()
    {
        return $this->hasOne(Rubric::class);
    }
}
