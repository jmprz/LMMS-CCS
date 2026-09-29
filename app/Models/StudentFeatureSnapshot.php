<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class StudentFeatureSnapshot extends Model
{
    protected $table = 'student_feature_snapshots';

    protected $fillable = [
        'user_id',
        'lab_session_id',
        'feature_cutoff_at',
        'feature_version',
        'quiz_avg',
        'quiz_attempt_count',
        'reading_avg_seconds',
        'reading_total_seconds',
        'material_session_count',
        'materials_accessed',
        'attendance_rate',
        'eligible_session_count',
        'task_avg',
        'submission_rate',
        'assigned_task_count',
        'additional_features',
    ];

    protected function casts(): array
    {
        return [
            'feature_cutoff_at' => 'datetime',
            'quiz_avg' => 'decimal:2',
            'reading_avg_seconds' => 'decimal:2',
            'attendance_rate' => 'decimal:2',
            'task_avg' => 'decimal:2',
            'submission_rate' => 'decimal:2',
            'additional_features' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function labSession(): BelongsTo
    {
        return $this->belongsTo(LabSession::class, 'lab_session_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(RiskPrediction::class, 'student_feature_snapshot_id');
    }
}

