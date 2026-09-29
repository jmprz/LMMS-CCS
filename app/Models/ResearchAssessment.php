<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class ResearchAssessment extends Model
{
    protected $table = 'research_assessments';

    protected $fillable = [
        'research_experiment_id',
        'quiz_id',
        'assessment_type',
        'assessment_version',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(ResearchExperiment::class, 'research_experiment_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}

