<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionGrade extends Model
{
    protected $fillable = [
        'submission_id',
        'rubric_id',
        'total_score',
        'max_score',
        'auto_graded',
        'feedback',
        'graded_by',
    ];

    protected $casts = [
        'auto_graded' => 'boolean',
        'feedback'    => 'array',
        'total_score' => 'decimal:2',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function rubric()
    {
        return $this->belongsTo(Rubric::class);
    }

    public function criterionScores()
    {
        return $this->hasMany(CriterionScore::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}