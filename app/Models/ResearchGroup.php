<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class ResearchGroup extends Model
{
    protected $table = 'research_groups';

    protected $fillable = [
        'research_experiment_id',
        'lab_session_id',
        'group_type',
        'recommendations_enabled',
    ];

    protected function casts(): array
    {
        return [
            'recommendations_enabled' => 'boolean',
        ];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(ResearchExperiment::class, 'research_experiment_id');
    }

    public function labSession(): BelongsTo
    {
        return $this->belongsTo(LabSession::class, 'lab_session_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ResearchParticipant::class, 'research_group_id');
    }
}

