<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    // Add this line if it's missing!
    protected $fillable = ['user_id', 'log_type', 'content', 'lab_session_id', 'duration_seconds'];

    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s.u',
    ];

    // This ensures Laravel sends the full precision to the DB
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s.u');
    }

    public function labSession()
    {
        // Fits perfectly if your foreign key column is 'lab_session_id'
        return $this->belongsTo(LabSession::class, 'lab_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}