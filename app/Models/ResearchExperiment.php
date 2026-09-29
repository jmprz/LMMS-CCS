<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class ResearchExperiment extends Model
{
    protected $table = 'research_experiments';

    protected $fillable = [
        'title',
        'description',
        'weak_topic_threshold',
        'intervention_starts_at',
        'intervention_ends_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'weak_topic_threshold' => 'decimal:2',
            'intervention_starts_at' => 'datetime',
            'intervention_ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ResearchGroup::class, 'research_experiment_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(ResearchAssessment::class, 'research_experiment_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(LearningRecommendation::class, 'research_experiment_id');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(RecommendationOutcome::class, 'research_experiment_id');
    }
}
