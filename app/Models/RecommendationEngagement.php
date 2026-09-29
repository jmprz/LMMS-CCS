<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationEngagement extends Model
{
    protected $fillable = [
        'learning_recommendation_id',
        'user_id',
        'opened_at',
        'closed_at',
        'last_heartbeat_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(LearningRecommendation::class, 'learning_recommendation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
