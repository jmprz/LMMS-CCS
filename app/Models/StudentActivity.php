<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentActivity extends Model
{
   protected $fillable = [
    'user_id', 
    'subject_id', 
    'activity_name', 
    'started_at', 
    'ended_at'
];
}
