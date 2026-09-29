<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class LearningRecommendation extends Model
{
    protected $table = 'learning_recommendations';

    protected $fillable = [
        'user_id',
        'lab_session_id',
        'quiz_attempt_id',
        'learning_topic_id',
        'material_id',
        'learning_resource_id',
        'risk_prediction_id',
        'research_experiment_id',
        'trigger_quiz_percentage',
        'status',
        'recommended_at',
        'displayed_at',
        'opened_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger_quiz_percentage' => 'decimal:2',
            'recommended_at' => 'datetime',
            'displayed_at' => 'datetime',
            'opened_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LearningTopic::class, 'learning_topic_id');
    }

    public function professorMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function libraryResource(): BelongsTo
    {
        return $this->belongsTo(LearningResource::class, 'learning_resource_id');
    }

    public function riskPrediction(): BelongsTo
    {
        return $this->belongsTo(RiskPrediction::class, 'risk_prediction_id');
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(ResearchExperiment::class, 'research_experiment_id');
    }
}

