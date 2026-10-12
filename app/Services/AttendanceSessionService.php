<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\LabSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Research freeze: v5 is frozen for live study collection. Semantic changes require
// a new feature version; see docs/research-freeze-v5.md before changing attendance.
class AttendanceSessionService
{
    public function authorizeManager(LabSession $class, User $actor): void
    {
        abort_unless($actor->role === 'admin' || ($actor->role === 'professor' && (int) $class->faculty_id === (int) $actor->id), 403);
    }

    public function start(LabSession $class, User $actor, int $lateAfterMinutes = 10): AttendanceSession
    {
        $this->authorizeManager($class, $actor);
        abort_unless($lateAfterMinutes >= 0 && $lateAfterMinutes <= 120, 422, 'Grace period must be between 0 and 120 minutes.');

        return DB::transaction(function () use ($class, $actor, $lateAfterMinutes) {
            // All lifecycle writes lock the same class first, serializing start/check-in/finalize races.
            $lockedClass = LabSession::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $active = AttendanceSession::where('lab_session_id', $class->id)->where('status', 'active')->first();
            if ($active) {
                return $active;
            }
            abort_unless($lockedClass->is_active, 422, 'Start the lab session before starting student attendance.');
            $started = now()->startOfSecond();
            $ids = DB::table('class_student as cs')->join('users as u', 'u.id', '=', 'cs.user_id')
                ->where('cs.lab_session_id', $class->id)->where('u.role', 'student')->where('cs.joined_at', '<=', $started)
                ->distinct()->pluck('cs.user_id');
            abort_if($ids->isEmpty(), 422, 'Enroll students before starting attendance.');
            $session = AttendanceSession::create([
                'lab_session_id' => $class->id, 'session_date' => $started->toDateString(), 'started_at' => $started,
                'status' => 'active', 'created_by' => $actor->id, 'late_after_minutes' => $lateAfterMinutes,
            ]);
            $session->students()->createMany($ids->map(fn ($id) => ['user_id' => $id, 'status' => 'pending'])->all());

            return $session;
        });
    }

    /** Only explicit check-ins create attendance evidence. A heartbeat never promotes pending rows. */
    public function checkIn(LabSession $class, User $student, ?int $expectedSessionId = null): ?array
    {
        abort_unless($student->role === 'student', 403);

        return DB::transaction(function () use ($class, $student, $expectedSessionId) {
            LabSession::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $session = AttendanceSession::where('lab_session_id', $class->id)->where('status', 'active')->first();
            if (! $session) {
                abort_if($expectedSessionId !== null, 409, 'Attendance session is no longer active.');

                return null;
            }
            abort_if($expectedSessionId !== null && $session->id !== $expectedSessionId, 409, 'Attendance session has changed. Refresh before checking in.');
            $row = $session->students()->where('user_id', $student->id)->first();
            abort_unless($row, 409, 'You are not on this session roster. Ask your instructor about attendance.');
            if ($row->checked_in_at === null && $row->status !== 'excused') {
                $time = now()->startOfSecond();
                $row->update(['status' => $time->gt($session->started_at->copy()->addMinutes($session->late_after_minutes)) ? 'late' : 'present',
                    'checked_in_at' => $time, 'last_seen_at' => $time, 'left_at' => null]);
            } elseif ($row->checked_in_at !== null) {
                $row->update(['last_seen_at' => now(), 'left_at' => null]);
            }

            return ['session_id' => $session->id, 'status' => $row->status, 'checked_in_at' => $row->checked_in_at?->toIso8601String()];
        });
    }

    public function presence(LabSession $class, User $student, bool $leaving = false): void
    {
        DB::transaction(function () use ($class, $student, $leaving) {
            LabSession::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $session = AttendanceSession::where('lab_session_id', $class->id)->where('status', 'active')->first();
            if (! $session) {
                return;
            }
            $row = $session->students()->where('user_id', $student->id)->whereNotNull('checked_in_at')->first();
            if ($row) {
                $row->update($leaving ? ['left_at' => now()] : ['last_seen_at' => now(), 'left_at' => null]);
            }
        });
    }

    public function close(AttendanceSession $session, User $actor, bool $cancel = false): AttendanceSession
    {
        $this->authorizeManager($session->labSession, $actor);

        return DB::transaction(function () use ($session, $cancel) {
            LabSession::whereKey($session->lab_session_id)->lockForUpdate()->firstOrFail();
            $session = AttendanceSession::whereKey($session->id)->firstOrFail();
            $target = $cancel ? 'cancelled' : 'finalized';
            if ($session->status === $target) {
                return $session;
            }
            abort_unless($session->status === 'active', 409, 'Closed attendance sessions cannot be changed.');
            $time = now()->startOfSecond();
            if (! $cancel) {
                $session->students()->where('status', 'pending')->update(['status' => 'absent', 'updated_at' => $time]);
            }
            $session->update(['status' => $target, 'ended_at' => $time, 'finalized_at' => $cancel ? null : $time]);

            return $session;
        });
    }

    public function leaveAll(User $student): void
    {
        $classIds = AttendanceSession::where('status', 'active')
            ->whereHas('students', fn ($query) => $query->where('user_id', $student->id)->whereNotNull('checked_in_at'))
            ->pluck('lab_session_id');
        foreach (LabSession::whereIn('id', $classIds)->get() as $class) {
            $this->presence($class, $student, true);
        }
    }

    public function excuse(AttendanceSession $session, User $actor, int $userId, bool $excused): void
    {
        $this->authorizeManager($session->labSession, $actor);
        DB::transaction(function () use ($session, $userId, $excused) {
            LabSession::whereKey($session->lab_session_id)->lockForUpdate()->firstOrFail();
            $session = AttendanceSession::whereKey($session->id)->firstOrFail();
            abort_unless($session->status === 'active', 409, 'Only active attendance can be reviewed.');
            $row = $session->students()->where('user_id', $userId)->firstOrFail();
            abort_unless(in_array($row->status, ['pending', 'excused']), 422, 'Only pending attendance can be excused.');
            $row->update(['status' => $excused ? 'excused' : 'pending']);
        });
    }

    public function summary(LabSession $class, bool $includeRoster = false): array
    {
        $active = AttendanceSession::where('lab_session_id', $class->id)->where('status', 'active')->first();
        $session = $active ?? AttendanceSession::where('lab_session_id', $class->id)->latest('id')->first();
        if (! $session) {
            return ['session' => null, 'counts' => null, 'roster' => []];
        }
        $counts = array_fill_keys(['pending', 'present', 'late', 'absent', 'excused'], 0);
        foreach ($session->students()->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->get() as $group) {
            $counts[$group->status] = (int) $group->total;
        }

        return ['session' => $session->only(['id', 'status', 'session_date', 'started_at', 'ended_at', 'finalized_at', 'late_after_minutes']),
            'counts' => ['expected' => array_sum($counts), ...$counts],
            'roster' => $includeRoster ? $session->students()->with('user:id,name')->orderBy('id')->get()->map(fn ($row) => [
                'user_id' => $row->user_id, 'name' => $row->user->name, 'status' => $row->status,
            ])->all() : []];
    }
}
