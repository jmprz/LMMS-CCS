<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AllowedSite;
use App\Models\BlockedAttempt;
use App\Models\LabSession;
use Illuminate\Support\Facades\DB;

class AllowedSiteController extends Controller
{
    /**
     * Get all allowed sites for a lab session
     */
    public function index($labSessionId)
    {
        $labSession = LabSession::findOrFail($labSessionId);
        
        // Get global pre-approved sites
        $preApprovedSites = AllowedSite::where('is_pre_approved', true)
            ->where('scope', 'global')
            ->get();

        // Get session-specific sites
        $sessionSites = AllowedSite::where('lab_session_id', $labSessionId)
            ->where('scope', 'global')
            ->with('addedBy')
            ->get();

        // Get task-specific sites
        $taskSites = AllowedSite::where('scope', 'task')
            ->whereHas('task', function($query) use ($labSessionId) {
                $query->where('subject_id', $labSessionId);
            })
            ->with(['task', 'addedBy'])
            ->get();

        return response()->json([
            'pre_approved' => $preApprovedSites,
            'session_sites' => $sessionSites,
            'task_sites' => $taskSites
        ]);
    }

    /**
     * Add a new allowed site
     */
    public function store(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'name' => 'required|string',
            'scope' => 'required|in:global,task',
            'lab_session_id' => 'required|exists:lab_sessions,id',
            'task_id' => 'nullable|exists:tasks,id',
            'description' => 'nullable|string'
        ]);

        // Clean domain
        $domain = $this->cleanDomain($request->domain);

        // Check if already exists
        $exists = AllowedSite::where('domain', $domain)
            ->where('scope', $request->scope)
            ->where('lab_session_id', $request->lab_session_id)
            ->when($request->task_id, function($query) use ($request) {
                $query->where('task_id', $request->task_id);
            })
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'This site is already in the whitelist'], 409);
        }

        $site = AllowedSite::create([
            'domain' => $domain,
            'name' => $request->name,
            'scope' => $request->scope,
            'lab_session_id' => $request->lab_session_id,
            'task_id' => $request->task_id,
            'description' => $request->description,
            'added_by' => auth()->id(),
            'is_pre_approved' => false
        ]);

        return response()->json([
            'success' => true,
            'site' => $site->load('addedBy')
        ]);
    }

    /**
     * Delete an allowed site
     */
    public function destroy($id)
    {
        $site = AllowedSite::findOrFail($id);

        // Can't delete pre-approved sites
        if ($site->is_pre_approved) {
            return response()->json(['error' => 'Cannot delete pre-approved educational sites'], 403);
        }

        $site->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get blocked attempts for a lab session
     */
    public function getBlockedAttempts($labSessionId)
    {
        $attempts = BlockedAttempt::where('lab_session_id', $labSessionId)
            ->with('user')
            ->orderBy('attempted_at', 'desc')
            ->take(50)
            ->get();

        return response()->json($attempts);
    }

    /**
     * Get blocked attempts statistics
     */
    public function getBlockedStats($labSessionId)
    {
        $total = BlockedAttempt::where('lab_session_id', $labSessionId)->count();
        
        $byDomain = BlockedAttempt::where('lab_session_id', $labSessionId)
            ->select('blocked_domain', DB::raw('count(*) as count'))
            ->groupBy('blocked_domain')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();

        $byStudent = BlockedAttempt::where('lab_session_id', $labSessionId)
            ->select('user_id', DB::raw('count(*) as count'))
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'total' => $total,
            'by_domain' => $byDomain,
            'by_student' => $byStudent
        ]);
    }

    /**
     * Clean domain from URL
     */
    private function cleanDomain($input)
    {
        // Remove protocol
        $input = preg_replace('#^https?://#', '', $input);
        
        // Remove www.
        $input = preg_replace('/^www\./', '', $input);
        
        // Remove path
        $input = parse_url('http://' . $input, PHP_URL_HOST) ?? $input;
        
        return strtolower(trim($input));
    }
}