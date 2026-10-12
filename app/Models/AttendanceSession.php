<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = ['lab_session_id', 'session_date', 'started_at', 'ended_at', 'finalized_at', 'status', 'created_by', 'late_after_minutes'];

    protected $casts = ['session_date' => 'date', 'started_at' => 'datetime', 'ended_at' => 'datetime', 'finalized_at' => 'datetime', 'late_after_minutes' => 'integer'];

    public function labSession(): BelongsTo
    {
        return $this->belongsTo(LabSession::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students(): HasMany
    {
        return $this->hasMany(AttendanceSessionStudent::class);
    }
}
