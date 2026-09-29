<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class LearningResource extends Model
{
    protected $table = 'learning_resources';

    protected $fillable = [
        'title',
        'description',
        'type',
        'content',
        'subject_key',
        'program',
        'status',
        'uploaded_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function learningTopics(): BelongsToMany
    {
        return $this->belongsToMany(
            LearningTopic::class,
            'learning_resource_topic',
            'learning_resource_id',
            'learning_topic_id'
        )->withTimestamps();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(
            LearningRecommendation::class,
            'learning_resource_id'
        );
    }
}

