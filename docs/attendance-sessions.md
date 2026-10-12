# Verified student attendance sessions

The additive migrations create `attendance_sessions` and `attendance_session_students`. They never copy, rewrite, delete or infer absences from `attendances`. Apply these migrations before deploying the integrated student controllers.

```shell
php artisan migrate
```

`LabSession::attendanceSessions()` owns meetings. `AttendanceSession::labSession()` and `creator()` reference the class and responsible user; `students()` owns the frozen roster. `AttendanceSessionStudent::attendanceSession()` and `user()` reference the meeting/student. `User::attendanceSessionEntries()` exposes historical roster entries independently of current membership. Foreign keys restrict deletion of classes/users/sessions with verified history; withdrawing a student by detaching class membership does not delete roster history. For a future account/class deletion or archival workflow, preserve this history explicitly instead of cascading it away.

## Lifecycle

Open the professor classroom, start the lab, then use **Start Attendance Session** for an actual student meeting. This is separate from the lab start/stop toggle and its legacy professor check-ins. Snapshot DISTINCT current students with membership `joined_at <= started_at`, excluding professors and future memberships. Pending is not absence while active. Class-row locking serializes lifecycle writes; starting again returns the same active session without resnapshotting. All session writes must go through the service to preserve one-active-session behavior.

The grace period is stored on each session, defaults to 10 minutes, and accepts 0–120 minutes. First check-in at or before `started_at + grace` is present; after that boundary it is late. This is a new operational collection rule, not an adviser-approved ML treatment of lateness. Repeated check-ins preserve first time/status. Existing desktop and mobile entry requests update the active roster. They never add someone absent from the snapshot. Students already in the room can explicitly check in to a newly started session through the client upon discovering it; server heartbeats only refresh `last_seen_at` for already checked-in entries, never convert pending to present. Departure updates `left_at`; reconnection clears it. These presence timestamps are operational signals, not active-duration measurements.

The professor panel shows expected/present/late/pending/absent/excused counts and the authorized session roster, including students later unenrolled. Pending students can be marked excused or returned to pending before finalization. No emails or school IDs are added to the roster payload. Endpoints require authentication, activation and the assigned professor role; the service additionally checks class ownership. Administrators are supported by the service but no additional admin UI is introduced in this change.

**Finalize Attendance** requires the professor to verify collection. Only pending becomes absent; present, late and excused remain. `ended_at`, `finalized_at` and status are stored atomically. Finalized sessions and their roster are closed to further check-in/excusal. **Cancel Attendance** records ended_at/status but does not fabricate absent entries or set finalized_at. Cancelled sessions cannot be finalized later. Stopping the lab does not silently finalize or cancel student attendance; return to the panel to finalize or cancel after reviewing collection. Missing/no check-in must only become absence when the instructor has verified the session collection; if unreliable, cancel instead of finalizing.

## Legacy compatibility

`attendances` remains legacy daily check-in/history, including existing professor entries. When a verified session is active, `markPresent` writes the new roster only and leaves legacy records untouched. When no verified session is active, the existing daily `updateOrCreate` path remains available for enrolled students; it is not verified evidence of absence. A request explicitly naming a closed/stale session fails rather than silently becoming a legacy write. No historical rows are automatically backfilled. New verified check-ins will not appear in legacy-only attendance displays until those views are separately updated.

## Verified attendance risk feature (v3)

`StudentRiskFeatureService` v3 uses only the student's frozen roster entries for the selected class whose session has status `finalized` and `finalized_at <= feature_cutoff_at`. Present and late count as attended; absent counts only in the denominator. Excused and anomalous pending entries are excluded. `eligible_session_count` is the count of present/late/absent entries, and `attendance_rate` is 100 times present/late count divided by that denominator. Zero countable entries produces NULL, including when legacy records exist. Finalized pending entries generate a warning containing class, cutoff and anomaly count, without student identifiers; exports continue.

Active/cancelled sessions, finalization after cutoff, missing roster entries and all legacy attendance are ignored. Current membership never supplies attendance eligibility; historical roster evidence permits feature extraction after withdrawal. Bulk exporters retain their existing enrollment-based dataset selection. The new version prevents old v2 snapshots from being reused for verified attendance. Other formulas and CSV columns are unchanged. The source-data audit still describes legacy attendance separately; legacy availability is not verified attendance coverage. No target labels or ML training are introduced.

Multiple meetings per day are represented by separate session IDs; there is no unique class/date constraint. Roster uniqueness is `(attendance_session_id, user_id)`. Session history is immutable through this API. Membership insertion timing remains the current platform enrollment proxy; formal enrollment-effective history, subgroup expected rosters, and later audited corrections are separate future improvements.
