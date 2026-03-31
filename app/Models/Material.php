<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    
protected $fillable = ['lab_session_id', 'title', 'type', 'content'];
}
