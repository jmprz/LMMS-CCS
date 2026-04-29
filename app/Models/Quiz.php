<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; 
use Illuminate\Database\Eloquent\Relations\BelongsTo; 
use Illuminate\Support\Facades\Auth;

class Quiz extends Model
{
    protected $fillable = [
        'title', 
        'subject_id', 
        'time_limit', 
        'published_at', 
        'expires_at'
    ];

    /**
     * The attributes that should be appended to the model's array form.
     * This is what makes quiz.has_attempt work in your Alpine.js code.
     */
    protected $appends = [
        'has_attempt', 
        'user_score', 
        'total_points'
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function labSession(): BelongsTo
    {
        return $this->belongsTo(LabSession::class, 'subject_id');
    }

    // Accessor for Total Points
    public function getTotalPointsAttribute()
    {
        return $this->questions->sum('points');
    }

    // Accessor for Has Attempt (Scoped to logged in user)
    public function getHasAttemptAttribute()
    {
        if (!Auth::check()) return false;
        return $this->attempts()->where('user_id', Auth::id())->exists();
    }

    // Accessor for User Score (Scoped to logged in user)
    public function getUserScoreAttribute()
    {
        if (!Auth::check()) return null;
        $attempt = $this->attempts()->where('user_id', Auth::id())->first();
        return $attempt ? $attempt->score : null;
    }
}