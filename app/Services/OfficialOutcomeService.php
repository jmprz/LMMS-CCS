<?php

namespace App\Services;

use App\Models\LabSession;
use App\Models\SubjectGrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OfficialOutcomeService
{
    public function authorize(User $actor, LabSession $class): void
    {
        abort_unless($actor->role === 'admin' || ($actor->role === 'professor' && (int) $class->faculty_id === (int) $actor->id), 403);
    }

    public function preview(User $actor, LabSession $class, string $csv, string $source, string $filename): object
    {
        $this->authorize($actor, $class);
        if (trim($source) === '' || mb_strlen($source) > 255) {
            $this->invalid('An official source/reference of at most 255 characters is required.');
        }
        if (strlen($csv) > 1048576) {
            $this->invalid('CSV exceeds 1 MB.');
        }
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $csv);
        rewind($stream);
        try {
            $header = fgetcsv($stream, escape: '');
            if ($header) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            }
            if ($header !== ['student_identifier', 'final_outcome']) {
                $this->invalid('CSV header must be student_identifier,final_outcome.');
            }
            $rows = [];
            $seen = [];
            $line = 1;
            while (($values = fgetcsv($stream, escape: '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($rows) >= 5000) {
                    $this->invalid('CSV exceeds 5000 rows.');
                }
                $identifier = trim((string) ($values[0] ?? ''));
                $value = trim((string) ($values[1] ?? ''));
                $errors = [];
                if (count($values) !== 2 || $identifier === '' || strlen($identifier) > 255) {
                    $errors[] = 'Invalid row/identifier.';
                }
                $duplicateKey = mb_strtolower($identifier);
                if (isset($seen[$duplicateKey])) {
                    $errors[] = 'Duplicate student row.';
                }
                $seen[$duplicateKey] = true;
                // Representational validation only, not an academic grade scale or threshold.
                if (! preg_match('/\A-?\d{1,4}(?:\.\d{1,2})?\z/', $value)) {
                    $errors[] = 'Outcome must be a numeric decimal fitting DECIMAL(6,2).';
                }
                $students = DB::table('users as u')->where('u.school_id', $identifier)->where('u.role', 'student')
                    ->whereExists(fn ($q) => $q->selectRaw('1')->from('class_student as cs')->whereColumn('cs.user_id', 'u.id')->where('cs.lab_session_id', $class->id))->pluck('u.id');
                if ($students->count() !== 1) {
                    $errors[] = 'Unmatched or ambiguous class member.';
                }
                $userId = $students->count() === 1 ? (int) $students[0] : null;
                if ($userId && isset($seen['user:'.$userId])) {
                    $errors[] = 'Duplicate student row.';
                }
                if ($userId) {
                    $seen['user:'.$userId] = true;
                }
                $grade = $userId ? $this->current($userId, $class->id) : null;
                $rows[] = ['line' => $line, 'student_identifier' => $identifier, 'user_id' => $userId,
                    'final_outcome' => $value, 'previous_outcome' => $grade?->grade, 'expected' => $this->fingerprint($grade), 'errors' => $errors];
            }
            if ($rows === []) {
                $this->invalid('CSV contains no outcomes.');
            }
        } finally {
            fclose($stream);
        }
        $id = (string) Str::uuid();
        DB::table('academic_outcome_imports')->insert(['id' => $id, 'lab_session_id' => $class->id, 'recorded_by' => $actor->id,
            'source' => $source, 'filename' => mb_substr(basename($filename), 0, 255), 'sha256' => hash('sha256', $csv),
            'preview' => json_encode($rows, JSON_THROW_ON_ERROR), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);

        return $this->batch($actor, $class, $id);
    }

    public function batch(User $actor, LabSession $class, string $id): object
    {
        $this->authorize($actor, $class);

        return DB::table('academic_outcome_imports')->where('id', $id)->where('lab_session_id', $class->id)
            ->where('recorded_by', $actor->id)->firstOrFail();
    }

    public function commit(User $actor, LabSession $class, string $id): int
    {
        $this->authorize($actor, $class);

        return DB::transaction(function () use ($actor, $class, $id) {
            $class = LabSession::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $this->authorize($actor, $class);
            $batch = DB::table('academic_outcome_imports')->where('id', $id)->where('lab_session_id', $class->id)
                ->where('recorded_by', $actor->id)->lockForUpdate()->firstOrFail();
            $rows = json_decode($batch->preview, true, 32, JSON_THROW_ON_ERROR);
            if ($batch->committed_at) {
                return count($rows);
            }
            if (now()->gt($batch->expires_at)) {
                $this->invalid('Preview expired; upload again.');
            }
            foreach ($rows as $row) {
                if ($row['errors']) {
                    $this->invalid('Resolve all preview errors before committing.');
                }
                $enrolled = DB::table('class_student as cs')->join('users as u', 'u.id', '=', 'cs.user_id')
                    ->where('cs.lab_session_id', $class->id)->where('cs.user_id', $row['user_id'])
                    ->where('u.school_id', $row['student_identifier'])->where('u.role', 'student')->lockForUpdate()->first();
                if (! $enrolled) {
                    $this->invalid('Class membership or identifier changed; preview again.');
                }
                $grade = $this->current($row['user_id'], $class->id);
                if ($this->fingerprint($grade) !== $row['expected']) {
                    $this->invalid('An outcome changed since preview; preview again.');
                }
                if ($grade && ! DB::table('subject_grade_revisions')->where('subject_grade_id', $grade->id)->exists()) {
                    $this->revision($grade, null); // Capture pre-existing official record before its first correction.
                }
                $grade ??= new SubjectGrade(['user_id' => $row['user_id'], 'lab_session_id' => $class->id, 'grading_period' => 'final']);
                $grade->grade = $row['final_outcome'];
                $grade->recorded_at = now();
                $grade->recorded_by = $actor->id;
                $grade->source = $batch->source;
                $grade->save();
                $this->revision($grade, $batch->id);
            }
            DB::table('academic_outcome_imports')->where('id', $id)->update(['committed_at' => now(), 'updated_at' => now()]);

            return count($rows);
        });
    }

    private function current(int $userId, int $classId): ?SubjectGrade
    {
        $rows = SubjectGrade::where('user_id', $userId)->where('lab_session_id', $classId)->whereRaw("LOWER(TRIM(grading_period)) = 'final'")->get();
        if ($rows->count() > 1) {
            $this->invalid('Ambiguous final-period records require administrative review.');
        }

        return $rows->first();
    }

    private function fingerprint(?SubjectGrade $grade): string
    {
        return hash('sha256', json_encode($grade?->getAttributes(), JSON_THROW_ON_ERROR));
    }

    private function revision(SubjectGrade $grade, ?string $importId): void
    {
        DB::table('subject_grade_revisions')->insert(['subject_grade_id' => $grade->id, 'grade' => $grade->grade,
            'recorded_at' => $grade->recorded_at, 'recorded_by' => $grade->recorded_by, 'source' => $grade->source,
            'import_id' => $importId, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['outcomes' => $message]);
    }
}
