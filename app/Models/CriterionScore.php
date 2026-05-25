<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriterionScore extends Model
{
    protected $fillable = [
        'submission_grade_id',
        'criterion_id',
        'points_earned',
        'max_points',
        'feedback',
        'auto_checked',
    ];

    protected $casts = [
        'auto_checked'  => 'boolean',
        'points_earned' => 'decimal:2',
    ];

    public function submissionGrade()
    {
        return $this->belongsTo(SubmissionGrade::class);
    }

    public function criterion()
    {
        return $this->belongsTo(RubricCriterion::class, 'criterion_id');
    }
}