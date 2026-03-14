<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
   protected $table = 'curriculum';
    protected $fillable = [
        'subject_code', 
        'subject_title', 
        'year_level', 
        'semester', 
        'prerequisite_id', 
        'syllabus_topics'
    ];
}