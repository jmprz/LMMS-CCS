<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class SubjectGrade extends Model
{
    protected $table = 'subject_grades';

    protected $fillable = [
        'user_id',
        'lab_session_id',
        'grading_period',
        'grade',
        'recorded_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
            'recorded_at' => 'datetime',
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
