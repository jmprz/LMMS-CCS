<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSessionStudent extends Model
{
    protected $fillable = ['attendance_session_id', 'user_id', 'status', 'checked_in_at', 'left_at', 'last_seen_at'];

    protected $casts = ['checked_in_at' => 'datetime', 'left_at' => 'datetime', 'last_seen_at' => 'datetime'];

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
