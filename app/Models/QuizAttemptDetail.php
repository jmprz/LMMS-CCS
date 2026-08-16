<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttemptDetail extends Model
{
    protected $fillable = [
        'quiz_attempt_id', 
        'question_id', 
        'is_correct',
        'points_earned', 
        'points_possible'
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
