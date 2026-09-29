<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{

    protected $fillable = ['lab_session_id', 'title', 'type', 'content'];
    public function learningTopics()
    {
        return $this->belongsToMany(LearningTopic::class, 'professor_material_topic', 'material_id', 'learning_topic_id')->withPivot('tagged_by')->withTimestamps();
    }
    public function labSession()
    {
        return $this->belongsTo(LabSession::class, 'lab_session_id');
    }
    public function learningRecommendations()
    {
        return $this->hasMany(LearningRecommendation::class, 'material_id');
    }
}



