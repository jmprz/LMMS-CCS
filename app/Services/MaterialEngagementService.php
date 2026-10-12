<?php

namespace App\Services;

use App\Models\{ActivityLog, Material, MaterialLog, User};
use Illuminate\Support\Facades\DB;

// Research freeze: v5 is frozen for live study collection. Semantic changes require
// a new feature version; see docs/research-freeze-v5.md before changing engagement.
class MaterialEngagementService
{
    public const VERSION = 'visible_v1';
    public const MAX_INTERVAL_SECONDS = 20;

    public function start(User $student, Material $material, string $clientKey, bool $external = false): MaterialLog
    {
        abort_unless($student->role === 'student' && DB::table('class_student')
            ->where('user_id', $student->id)->where('lab_session_id', $material->lab_session_id)
            ->where('joined_at', '<=', now())->exists(), 403);

        return DB::transaction(function () use ($student, $material, $clientKey, $external) {
            // Serialize start retries, including concurrent requests with the same key.
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $existing = MaterialLog::where('user_id', $student->id)->where('client_session_key', $clientKey)->first();
            if ($existing) {
                abort_unless((int) $existing->material_id === (int) $material->id, 409);
                return $existing;
            }
            $internal = ! $external && ($material->type === 'youtube'
                || ($material->type === 'pdf' && ! preg_match('/\Ahttps?:\/\//i', $material->content)));

            return MaterialLog::create([
                'user_id' => $student->id, 'material_id' => $material->id, 'client_session_key' => $clientKey,
                'collection_version' => self::VERSION, 'measurement_mode' => $internal ? 'internal_visible' : 'external_open',
                'opened_at' => now(), 'last_heartbeat_at' => now(), 'last_sequence' => 0,
                'reported_active_seconds' => 0, 'duration_seconds' => null,
            ]);
        });
    }

    public function record(User $student, Material $material, int $sessionId, int $sequence, int $activeSeconds, bool $close = false): MaterialLog
    {
        return DB::transaction(function () use ($student, $material, $sessionId, $sequence, $activeSeconds, $close) {
            $log = MaterialLog::whereKey($sessionId)->where('user_id', $student->id)
                ->where('material_id', $material->id)->where('collection_version', self::VERSION)
                ->lockForUpdate()->firstOrFail();
            if ($log->closed_at || $sequence <= $log->last_sequence) {
                return $log;
            }
            abort_if($activeSeconds < $log->reported_active_seconds, 422, 'Active time must be cumulative.');
            $now = now();
            $elapsed = max(0, $now->getTimestamp() - $log->last_heartbeat_at->getTimestamp());
            $delta = $activeSeconds - $log->reported_active_seconds;
            if ($log->measurement_mode === 'internal_visible') {
                $log->duration_seconds = ($log->duration_seconds ?? 0) + min($delta, $elapsed, self::MAX_INTERVAL_SECONDS);
            }
            // External records always keep duration NULL; no outside-page measurement is claimed.
            $log->last_heartbeat_at = $now;
            $log->last_sequence = $sequence;
            $log->reported_active_seconds = $activeSeconds;
            if ($close) {
                $log->closed_at = $now;
                ActivityLog::create([
                    'user_id' => $student->id, 'lab_session_id' => $material->lab_session_id, 'log_type' => 'material',
                    'content' => 'Closed material viewer: "'.$material->title.'"', 'duration_seconds' => $log->duration_seconds,
                ]);
            }
            $log->save();

            return $log;
        });
    }
}
