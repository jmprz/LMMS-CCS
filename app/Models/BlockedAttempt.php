<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lab_session_id',
        'blocked_url',
        'blocked_domain',
        'reason',
        'ip_address',
        'attempted_at'
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function labSession()
    {
        return $this->belongsTo(LabSession::class);
    }

    public static function logAttempt($userId, $labSessionId, $url, $reason = 'not_whitelisted')
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $domain = $domain ? preg_replace('/^www\./', '', $domain) : 'unknown';

        return self::create([
            'user_id' => $userId,
            'lab_session_id' => $labSessionId,
            'blocked_url' => $url,
            'blocked_domain' => $domain,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'attempted_at' => now()
        ]);
    }
}