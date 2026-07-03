<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BlockedAttempt;
use App\Models\LabSession;
use App\Models\User;

class ViolationEnforcementService
{
    public function getThreshold(LabSession $session): int
    {
        return max(1, (int) ($session->violation_warning_threshold ?? config('lmms.violation_warning_threshold', 3)));
    }

    public function getStatus(int $userId, int $labSessionId): array
    {
        $session = LabSession::find($labSessionId);
        $threshold = $session ? $this->getThreshold($session) : config('lmms.violation_warning_threshold', 3);
        $pivot = $this->getPivot($userId, $labSessionId);

        $violationCount = (int) ($pivot?->violation_count ?? 0);
        $isBlocked = (bool) ($pivot?->is_screen_blocked ?? false);

        return [
            'violation_count' => $violationCount,
            'threshold' => $threshold,
            'remaining_warnings' => max(0, $threshold - $violationCount),
            'is_screen_blocked' => $isBlocked,
        ];
    }

    public function recordViolation(int $userId, int $labSessionId, string $detail, ?string $blockedUrl = null): array
    {
        $session = LabSession::findOrFail($labSessionId);
        $threshold = $this->getThreshold($session);
        $user = User::findOrFail($userId);

        if ($blockedUrl) {
            BlockedAttempt::logAttempt($userId, $labSessionId, $blockedUrl, 'not_whitelisted');
        }

        $pivot = $this->getPivot($userId, $labSessionId);
        if (!$pivot) {
            $user->joinedClasses()->attach($labSessionId, [
                'violation_count' => 0,
                'is_screen_blocked' => false,
            ]);
            $currentCount = 0;
            $isBlocked = false;
        } else {
            $currentCount = (int) $pivot->violation_count;
            $isBlocked = (bool) $pivot->is_screen_blocked;
        }

        if ($isBlocked) {
            ActivityLog::create([
                'user_id' => $userId,
                'log_type' => 'alert',
                'content' => $detail,
                'lab_session_id' => $labSessionId,
            ]);

            return $this->buildResponse('blocked', $currentCount, $threshold, true);
        }

        $newCount = $currentCount + 1;
        $updateData = ['violation_count' => $newCount];
        $action = 'warning';

        if ($newCount >= $threshold) {
            $updateData['is_screen_blocked'] = true;
            $updateData['screen_blocked_at'] = now();
            $action = 'blocked';
        }

        $user->joinedClasses()->updateExistingPivot($labSessionId, $updateData);

        ActivityLog::create([
            'user_id' => $userId,
            'log_type' => $action === 'blocked' ? 'alert' : 'violation',
            'content' => $detail,
            'lab_session_id' => $labSessionId,
        ]);

        return $this->buildResponse($action, $newCount, $threshold, $action === 'blocked');
    }

    public function unblock(int $userId, int $labSessionId, bool $resetCount = true): void
    {
        $update = [
            'is_screen_blocked' => false,
            'screen_blocked_at' => null,
        ];

        if ($resetCount) {
            $update['violation_count'] = 0;
        }

        User::findOrFail($userId)
            ->joinedClasses()
            ->updateExistingPivot($labSessionId, $update);
    }

    private function getPivot(int $userId, int $labSessionId)
    {
        return User::find($userId)
            ?->joinedClasses()
            ->where('lab_session_id', $labSessionId)
            ->first()
            ?->pivot;
    }

    private function buildResponse(string $action, int $count, int $threshold, bool $isBlocked): array
    {
        $remaining = max(0, $threshold - $count);

        if ($action === 'blocked') {
            $message = "Your screen has been locked after {$threshold} policy violation(s). Please wait for your instructor to unblock you.";
        } else {
            $message = "Policy violation recorded ({$count}/{$threshold}). You have {$remaining} warning(s) remaining before your screen is locked.";
        }

        return [
            'action' => $action,
            'violation_count' => $count,
            'threshold' => $threshold,
            'remaining_warnings' => $remaining,
            'is_screen_blocked' => $isBlocked,
            'message' => $message,
        ];
    }
}
