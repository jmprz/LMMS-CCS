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

}
