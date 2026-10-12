<?php

namespace App\Services;

use App\Models\ActivityDurationSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

// Research freeze: v5 is frozen for live study collection. Semantic changes require
// a new feature version; see docs/research-freeze-v5.md before changing duration.
class ActivityDurationService
{
    public const VERSION = 'generic_ui_v1';

    public const MAX_INTERVAL_SECONDS = 20;

    public function start(User $student, int $classId, string $key): ActivityDurationSession
    {
        abort_unless($student->role === 'student' && DB::table('class_student')->where('user_id', $student->id)
            ->where('lab_session_id', $classId)->where('joined_at', '<=', now())->exists(), 403);

        return DB::transaction(function () use ($student, $classId, $key) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $existing = ActivityDurationSession::where('user_id', $student->id)->where('client_session_key', $key)->first();
            if ($existing) {
                abort_unless((int) $existing->lab_session_id === $classId, 409);

                return $existing;
            }
            // One collecting classroom per student; replacement adds no elapsed seconds.
            ActivityDurationSession::where('user_id', $student->id)->whereNull('closed_at')
                ->update(['closed_at' => now(), 'updated_at' => now()]);

            return ActivityDurationSession::create([
                'user_id' => $student->id, 'lab_session_id' => $classId, 'client_session_key' => $key,
                'collection_version' => self::VERSION, 'opened_at' => now(), 'last_heartbeat_at' => now(), 'duration_seconds' => null,
            ]);
        });
    }

    public function record(User $student, int $classId, int $id, int $sequence, int $seconds, bool $close): ActivityDurationSession
    {
        abort_unless($student->role === 'student', 403);

        return DB::transaction(function () use ($student, $classId, $id, $sequence, $seconds, $close) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $session = ActivityDurationSession::whereKey($id)->where('user_id', $student->id)
                ->where('lab_session_id', $classId)->where('collection_version', self::VERSION)->lockForUpdate()->firstOrFail();
            if ($session->closed_at || $sequence <= $session->last_sequence) {
                return $session;
            }
            abort_if($seconds < $session->reported_active_seconds, 422, 'Active time must be cumulative.');
            $elapsed = max(0, now()->getTimestamp() - $session->last_heartbeat_at->getTimestamp());
            $session->duration_seconds = ($session->duration_seconds ?? 0)
                + min($seconds - $session->reported_active_seconds, $elapsed, self::MAX_INTERVAL_SECONDS);
            $session->last_sequence = $sequence;
            $session->reported_active_seconds = $seconds;
            $session->last_heartbeat_at = now();
            if ($close) {
                $session->closed_at = now();
            }
            $session->save();

            return $session;
        });
    }

    public function evidenceQuery(CarbonInterface $cutoff): Builder
    {
        // Mutable totals updated after cutoff are excluded, never back-projected.
        return DB::table('activity_duration_sessions')->where('collection_version', self::VERSION)
            ->where('opened_at', '<=', $cutoff)->where('created_at', '<=', $cutoff)
            ->where('updated_at', '<=', $cutoff)->whereNotNull('duration_seconds')->where('duration_seconds', '>=', 0);
    }
}
