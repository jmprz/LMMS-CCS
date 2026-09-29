<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class LearningTopic extends Model
{
    protected $table = 'learning_topics';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'created_by',
    ];

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(LearningResource::class, 'learning_resource_topic');
    }

    public function professorMaterials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'professor_material_topic');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'learning_topic_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(LearningRecommendation::class, 'learning_topic_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function learningResources()
    {
        return $this->belongsToMany(
            LearningResource::class,
            'learning_resource_topic'
        )->withTimestamps();
    }
}

