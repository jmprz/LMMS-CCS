<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; 
use Illuminate\Database\Eloquent\Relations\BelongsTo; 

class Quiz extends Model
{
protected $fillable = [
    'title', 
    'subject_id', 
    'time_limit', 
    'published_at', 
    'expires_at'
];

public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Question::class);
}

public function getTotalPointsAttribute()
{
    return $this->questions->sum('points');
}
public function labSession()
{
    return $this->belongsTo(LabSession::class);
}

public function attempts(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(QuizAttempt::class);
}
}
