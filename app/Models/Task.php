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
public function submissions()
{
    return $this->hasMany(Submission::class);
}

public function currentUserSubmission()
{
    return $this->hasOne(Submission::class)->where('user_id', auth()->id());
}
// app/Models/LabSession.php

public function quizzes()
{
    // Make sure 'subject_id' matches the column name in your quizzes table
    return $this->hasMany(\App\Models\Quiz::class, 'subject_id');
}

public function tasks()
{
    return $this->hasMany(\App\Models\Task::class, 'subject_id');
}
}
