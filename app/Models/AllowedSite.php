<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllowedSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'name',
        'scope',
        'task_id',
        'lab_session_id',
        'is_pre_approved',
        'description',
        'added_by'
    ];

    protected $casts = [
        'is_pre_approved' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function labSession()
    {
        return $this->belongsTo(LabSession::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public static function isUrlAllowed($url, $labSessionId = null, $taskId = null)
    {
        $domain = parse_url($url, PHP_URL_HOST);
        
        if (!$domain) {
            return false;
        }

        // Remove 'www.' prefix
        $domain = preg_replace('/^www\./', '', $domain);

        // Check global pre-approved sites
        $globalAllowed = self::where('is_pre_approved', true)
            ->where('scope', 'global')
            ->where(function($query) use ($domain) {
                $query->where('domain', $domain)
                      ->orWhere('domain', 'like', '%' . $domain);
            })
            ->exists();

        if ($globalAllowed) {
            return true;
        }

        // Check task-specific whitelist
        if ($taskId) {
            $taskAllowed = self::where('scope', 'task')
                ->where('task_id', $taskId)
                ->where(function($query) use ($domain) {
                    $query->where('domain', $domain)
                          ->orWhere('domain', 'like', '%' . $domain);
                })
                ->exists();

            if ($taskAllowed) {
                return true;
            }
        }

        // Check session-specific whitelist
        if ($labSessionId) {
            $sessionAllowed = self::where('scope', 'global')
                ->where('lab_session_id', $labSessionId)
                ->where(function($query) use ($domain) {
                    $query->where('domain', $domain)
                          ->orWhere('domain', 'like', '%' . $domain);
                })
                ->exists();

            if ($sessionAllowed) {
                return true;
            }
        }

        return false;
    }

    public static function extractDomain($url)
    {
        $domain = parse_url($url, PHP_URL_HOST);
        return $domain ? preg_replace('/^www\./', '', $domain) : null;
    }
}