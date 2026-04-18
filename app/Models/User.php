<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Role Helper Methods
     * These make your Blade and Controller logic much cleaner.
     */
    public function isAdmin() { return $this->role === 'admin'; }
    public function isProfessor() { return $this->role === 'professor'; }
    public function isStudent() { return $this->role === 'student'; }

    /**
     * Relationship for PROFESSORS
     * A Professor "has many" lab sessions they are teaching.
     */
    public function managedSessions()
    {
        return $this->hasMany(LabSession::class, 'faculty_id');
    }

    /**
     * Relationship for STUDENTS
     * (Your existing joinedClasses code)
     */
    public function joinedClasses()
    {
        return $this->belongsToMany(LabSession::class, 'class_student', 'user_id', 'lab_session_id')
                    ->withPivot('is_present')
                    ->withTimestamps();
    }

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'name',
        'email',
        'password',
        'school_id',
        'role',
        'program',
        'year_level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // app/Models/User.php

public function attendances()
{
    // A user can have many attendance records
    return $this->hasMany(\App\Models\Attendance::class);
}
}