<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    // These must match your database column names EXACTLY
    protected $fillable = [
        'user_id', 
        'quiz_id', 
        'score', 
        'total_questions',
        'time_spent'
    ];

 public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(User::class);
}
    public function quiz() {
        return $this->belongsTo(Quiz::class);
    }

public function details()
{
    return $this->hasMany(QuizAttemptDetail::class);
}
    
}