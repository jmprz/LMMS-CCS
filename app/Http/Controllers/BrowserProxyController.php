<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AllowedSite;
use App\Models\BlockedAttempt;

class BrowserProxyController extends Controller
{
    /**
     * Check if a URL is allowed
     */
    public function checkUrl(Request $request)
    {
        $url = $request->input('url');
        $labSessionId = $request->input('lab_session_id');
        $taskId = $request->input('task_id');
        $userId = auth()->id();

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'allowed' => false,
                'reason' => 'Invalid URL format'
            ]);
        }

        // Check if URL is allowed
        $allowed = AllowedSite::isUrlAllowed($url, $labSessionId, $taskId);

        if (!$allowed) {
            // Log blocked attempt
            BlockedAttempt::logAttempt($userId, $labSessionId, $url, 'not_whitelisted');
        }

        return response()->json([
            'allowed' => $allowed,
            'domain' => AllowedSite::extractDomain($url),
            'reason' => $allowed ? null : 'This website is not allowed during lab sessions.'
        ]);
    }
}