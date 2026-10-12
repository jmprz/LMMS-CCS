<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskPrediction extends Model
{
    protected $fillable = [
        'student_feature_snapshot_id','model_name','model_version',
        'risk_level','risk_probability','predicted_at','explanation',
    ];

    protected $casts = [
        'risk_probability' => 'decimal:5',
        'predicted_at' => 'datetime',
        'explanation' => 'array',
    ];

    public function featureSnapshot(): BelongsTo
    {
        return $this->belongsTo(StudentFeatureSnapshot::class, 'student_feature_snapshot_id');
    }

    public function learningRecommendations(): HasMany
    {
        return $this->hasMany(LearningRecommendation::class, 'risk_prediction_id');
    }
}
