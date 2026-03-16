<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentResult extends Model
{
  protected $fillable = [
        'user_id',
        'task_id',
        'score',
        'answer_accuracy',
        'response_time_ms'
    ];
}
