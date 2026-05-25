<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RubricCriterion extends Model
{
    protected $fillable = [
        'rubric_id',
        'criterion_name',
        'description',
        'max_points',
        'checking_type',
        'checking_rules',
        'weight',
        'order_index',
    ];

    protected $casts = [
        'checking_rules' => 'array',
        'weight'         => 'decimal:2',
    ];

    public function rubric()
    {
        return $this->belongsTo(Rubric::class);
    }

    public function criterionScores()
    {
        return $this->hasMany(CriterionScore::class, 'criterion_id');
    }
}