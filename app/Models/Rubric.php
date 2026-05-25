<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rubric extends Model
{
    protected $fillable = [
        'task_id',
        'name',
        'description',
        'total_points',
        'auto_grade_enabled',
        'created_by',
    ];

    protected $casts = [
        'auto_grade_enabled' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function criteria()
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('order_index');
    }

    public function submissionGrades()
    {
        return $this->hasMany(SubmissionGrade::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}