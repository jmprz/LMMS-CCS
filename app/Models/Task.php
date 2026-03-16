<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'subject_id',
        'title',
        'description',
        'deadline',
        'points'
    ];

    // Optional: If you need to link back to the Curriculum
 public function labSession()
{
    return $this->belongsTo(LabSession::class, 'subject_id');
}
}
