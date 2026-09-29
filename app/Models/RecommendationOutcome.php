<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class RecommendationOutcome extends Model
{
    protected $table = 'recommendation_outcomes';

    protected $fillable = [
        'research_experiment_id',
        'user_id',
        'learning_topic_id',
        'pre_quiz_attempt_id',
        'post_quiz_attempt_id',
        'pre_score_percentage',
        'post_score_percentage',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'pre_score_percentage' => 'decimal:2',
            'post_score_percentage' => 'decimal:2',
            'assessed_at' => 'datetime',
        ];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(ResearchExperiment::class, 'research_experiment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LearningTopic::class, 'learning_topic_id');
    }

    public function preAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'pre_quiz_attempt_id');
    }

    public function postAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'post_quiz_attempt_id');
    }
}

