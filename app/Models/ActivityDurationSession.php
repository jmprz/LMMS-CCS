<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityDurationSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'opened_at' => 'datetime', 'closed_at' => 'datetime', 'last_heartbeat_at' => 'datetime',
        'duration_seconds' => 'integer', 'last_sequence' => 'integer', 'reported_active_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function labSession(): BelongsTo
    {
        return $this->belongsTo(LabSession::class);
    }
}
