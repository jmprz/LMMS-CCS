<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\LabSession;
use App\Models\QuizAttempt;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    public function create(Request $request)
    {
        $sessionId = $request->query('session_id');
        $currentSession = LabSession::findOrFail($sessionId);

        return view('professor.quizzes.create', compact('currentSession'));
    }

    public function store(Request $request)
    {
        // 1. Flexible Validation
        $request->validate([
            'title' => 'required|string|max:255',
            'lab_session_id' => 'required|exists:lab_sessions,id',
            'time_limit' => 'required|integer|min:1',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.type' => 'required|string',
            'questions.*.points' => 'required|integer|min:1',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:published_at',
        ]);

        return DB::transaction(function () use ($request) {
            // 2. Create the Quiz
            $quiz = Quiz::create([
                'title' => $request->title,
                'subject_id' => $request->lab_session_id,
                'time_limit' => $request->time_limit,
                'published_at' => $request->published_at ?? now(),
                'expires_at' => $request->expires_at,
            ]);

            // 3. Single loop through questions
            foreach ($request->questions as $qData) {
                $question = $quiz->questions()->create([
                    'question_text' => $qData['text'],
                    'points' => $qData['points'],
                    'type' => $qData['type'],
                ]);

                // 4. Handle Option Saving based on Type
                if ($qData['type'] === 'true_false') {
                    foreach ($qData['options'] as $index => $oText) {
                        $question->options()->create([
                            'option_text' => $oText,
                            'is_correct' => $oText === $qData['correct_option'], // compare text, not index
                        ]);
                    }
                } elseif ($qData['type'] === 'multiple') {
                    foreach ($qData['options'] as $index => $oText) {
                        $question->options()->create([
                            'option_text' => $oText,
                            'is_correct' => $index == $qData['correct_option'],
                        ]);
                    }
                } elseif ($qData['type'] === 'select_all') {
                    foreach ($qData['options'] as $index => $oText) {
                        $question->options()->create([
                            'option_text' => $oText,
                            'is_correct' => in_array($index, $qData['correct_options'] ?? []),
                        ]);
                    }
                } elseif ($qData['type'] === 'identification') {
                    $question->options()->create([
                        'option_text' => $qData['answer'],
                        'is_correct' => true,
                    ]);
                }
            }

            $this->logProfessorActivity(
                $quiz->subject_id,
                'Posted a quiz: "' . $quiz->title . '"'
            );

            // =========================================================================
            // LIVE PRODUCTION EMAIL ALERTS FOR QUIZZES (SYNCHRONOUS TRANSMISSION)
            // =========================================================================
            try {
                // Find the structural laboratory session container to extract the name
                $labSession = \App\Models\LabSession::find($request->lab_session_id);

                // Fetch students enrolled in this laboratory class pivot block
                $students = \App\Models\User::whereHas('joinedClasses', function ($query) use ($request) {
                    $query->where('lab_sessions.id', $request->lab_session_id);
                })->get();

                // Fire the direct synchronous outbound mail distribution block
                foreach ($students as $student) {
                    \Illuminate\Support\Facades\Mail::send('emails.new_quiz_notification', [
                        'student' => $student,
                        'quiz' => $quiz,
                        'labSession' => $labSession
                    ], function ($message) use ($student, $quiz) {
                        $message->to($student->email)
                            ->subject('LMMS - New Quiz Assigned: ' . $quiz->title);
                    });
                }
            } catch (\Exception $e) {
                // Prevent connection disruptions or mail driver errors from rolling back the DB record
                \Log::error('Quiz Email Notification System Encountered an Error: ' . $e->getMessage());
            }
            // =========================================================================

            return redirect()->route('professor.classroom.show', $request->lab_session_id)
                ->with('success', 'Quiz created successfully!');
        });
    }


    public function attempt($id)
    {
        // 1. Load the quiz with its questions and choices
        $quiz = Quiz::with('questions.options')->findOrFail($id);

        // 2. Check if this specific student has already completed the assessment
        $attempt = \App\Models\QuizAttempt::where('quiz_id', $id)
            ->where('user_id', auth()->id())
            ->first();

        // 3. Set up the flags to control the modal screen
        $completed = $attempt ? true : false;
        $studentScore = $attempt ? $attempt->score : 0;

        // 3b. Enforce the publish/deadline window for students who haven't already submitted
        $notYetAvailable = !$completed && $quiz->published_at && now()->lt($quiz->published_at);
        $deadlinePassed = !$completed && $quiz->expires_at && now()->gt($quiz->expires_at);

        // 4. Randomize question and option order for this student
        $questions = $this->randomizedQuestionsForStudent($quiz);

        // 5. Send the scoring context straight down to the view template
        return view('student.quizzes.attempt', compact(
            'quiz', 'completed', 'studentScore', 'questions', 'notYetAvailable', 'deadlinePassed'
        ));
    }

    private function randomizedQuestionsForStudent(Quiz $quiz)
    {
        $userId = auth()->id();

        return $quiz->questions
            ->map(function ($question) use ($userId, $quiz) {
                $question->setRelation(
                    'options',
                    $question->options
                        ->sortBy(fn($option) => crc32("{$userId}:{$quiz->id}:{$question->id}:{$option->id}"))
                        ->values()
                );

                return $question;
            })
            ->sortBy(fn($question) => crc32("{$userId}:{$quiz->id}:{$question->id}"))
            ->values();
    }

    public function submit(Request $request, $quizId)
    {
        try {
            return \DB::transaction(function () use ($request, $quizId) {
                $quiz = Quiz::with('questions.options')->findOrFail($quizId);

                // 0. Reject attempts that were *started* after the deadline (defense in depth —
                // the "attempt" screen already blocks starting a new quiz past expires_at, this
                // guards the submit endpoint itself against direct calls). A student who started
                // before the deadline but whose time_limit runs past it is still allowed to finish.
                $startTime = $request->input('start_time') ? \Carbon\Carbon::parse($request->input('start_time')) : now();

                if ($quiz->expires_at && $startTime->gt($quiz->expires_at)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The deadline for this quiz has passed. Your answers were not submitted.',
                    ], 422);
                }

                $totalQuestions = $quiz->questions->count();
                $totalPoints = $quiz->questions->sum('points'); // ← denominator now reflects weighted points
                $score = 0; // will accumulate POINTS earned, not question count
                $details = [];

                $userAnswers = collect($request->input('answers', []));

                foreach ($quiz->questions as $question) {
                    $userAnswer = $userAnswers->get($question->id);
                    $isCorrect = false;
                    $pointsPossible = $question->points;
                    $pointsEarned = 0;

                    // 1. Get ALL correct option texts
                    $correctOptions = $question->options
                        ->where('is_correct', true)
                        ->pluck('option_text')
                        ->map(fn($text) => strtolower(trim($text)))
                        ->toArray();

                    // 2. Normalize user input
                    $userAnswerArray = is_array($userAnswer) ? $userAnswer : [$userAnswer];
                    $cleanUser = array_map(fn($ans) => strtolower(trim($ans)), array_filter($userAnswerArray));

                    // 3. Compare (handles both single radio and multi-select checkbox)
                    if (count($cleanUser) > 0) {
                        sort($cleanUser);
                        sort($correctOptions);

                        if ($cleanUser === $correctOptions) {
                            $isCorrect = true;
                            $pointsEarned = $pointsPossible; // ← award the question's actual point value
                            $score += $pointsEarned;
                        }
                    }

                    $details[] = [
                        'question_id' => $question->id,
                        'is_correct' => $isCorrect,
                        'points_earned' => $pointsEarned,
                        'points_possible' => $pointsPossible,
                    ];
                }

                // 4. Create Attempt & STOPWATCH DURATION CALCULATION
                $timeSpent = abs(now()->getTimestamp() - $startTime->getTimestamp());

                $attempt = \App\Models\QuizAttempt::create([
                    'user_id' => auth()->id(),
                    'quiz_id' => $quiz->id,
                    'score' => $score,                 // now points earned
                    'total_questions' => $totalQuestions,
                    'total_points' => $totalPoints,    // ← new column
                    'time_spent' => $timeSpent,
                ]);

                // 5. Save Details
                foreach ($details as $detail) {
                    \App\Models\QuizAttemptDetail::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $detail['question_id'],
                        'is_correct' => $detail['is_correct'],
                        'points_earned' => $detail['points_earned'],
                        'points_possible' => $detail['points_possible'],
                    ]);
                }

                // 6. Timeline Log
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'log_type' => 'quiz',
                    'content' => "Student completed quiz assessment: \"" . $quiz->title . "\"",
                    'lab_session_id' => $quiz->subject_id,
                    'duration_seconds' => $timeSpent,
                ]);

                return response()->json([
                    'success' => true,
                    'score' => $score,           // points earned
                    'total' => $totalPoints,     // ← points possible, not question count
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing quiz: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);

        $this->logProfessorActivity(
            $quiz->subject_id,
            'Deleted the quiz: "' . $quiz->title . '"'
        );

        $quiz->delete(); // This removes the quiz and related attempts if cascade is on

        return redirect()->back()->with('success', 'Quiz deleted successfully!');
    }

    public function exportScores(Quiz $quiz): StreamedResponse
    {
        $quiz->load(['labSession', 'attempts.user']);

        $session = $quiz->labSession;
        if (!$session || ($session->faculty_id !== auth()->id() && auth()->user()->role !== 'admin')) {
            abort(403, 'Unauthorized access to this quiz.');
        }

        $attemptsByUserId = $quiz->attempts->keyBy('user_id');
        $defaultTotalQuestions = $quiz->questions()->count();
        $students = $session->students()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['users.id', 'users.school_id', 'users.first_name', 'users.last_name', 'users.email']);

        $filename = Str::slug($quiz->title) . '-quiz-scores-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($quiz, $session, $students, $attemptsByUserId, $defaultTotalQuestions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['Quiz', $quiz->title]);
            fputcsv($handle, ['Class', $session->subject_name]);
            fputcsv($handle, ['Exported At', now()->format('M d, Y g:i A')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'School ID',
                'Last Name',
                'First Name',
                'Email',
                'Score',
                'Total Questions',
                'Percentage',
                'Time Taken',
                'Status',
            ]);

            foreach ($students as $student) {
                $attempt = $attemptsByUserId->get($student->id);
                $defaultTotalPoints = $quiz->questions()->sum('points');
                $totalPoints = $attempt?->total_points ?? $defaultTotalPoints;
                $percentage = ($attempt && $totalPoints > 0)
                    ? round(($attempt->score / $totalPoints) * 100, 1) . '%'
                    : '';

                fputcsv($handle, [
                    $student->school_id,
                    $student->last_name,
                    $student->first_name,
                    $student->email,
                    $attempt?->score ?? '',
                    $attempt ? $totalPoints : '',
                    $percentage,
                    $attempt ? $this->formatQuizTimeSpent($attempt->time_spent) : '',
                    $attempt ? 'Submitted' : 'Not Submitted',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatQuizTimeSpent(?int $seconds): string
    {
        if ($seconds === null) {
            return '';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return "{$minutes}m {$remainingSeconds}s";
    }

    public function edit($id)
    {
        $quiz = Quiz::with(['questions.options', 'labSession'])->findOrFail($id);
        $hasAttempts = $quiz->attempts()->exists();

        $initialQuestions = $quiz->questions->map(function ($q) {
            $base = [
                'type' => $q->type,
                'text' => $q->question_text,
                'points' => $q->points,
                'options' => $q->options->pluck('option_text')->values()->all(),
            ];

            if ($q->type === 'multiple') {
                $base['correct'] = $q->options->search(fn($o) => $o->is_correct) ?: 0;
            } elseif ($q->type === 'true_false') {
                $correctOption = $q->options->firstWhere('is_correct', true);
                $base['correct'] = $correctOption->option_text ?? 'True';
            } elseif ($q->type === 'select_all') {
                $base['correct'] = $q->options->values()
                    ->filter(fn($o) => $o->is_correct)
                    ->keys()->values()->all();
            } elseif ($q->type === 'identification') {
                $base['answer'] = $q->options->first()->option_text ?? '';
            }

            return $base;
        });

         return view('professor.quizzes.edit', compact('quiz', 'initialQuestions', 'hasAttempts'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'time_limit' => 'required|integer|min:1',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.type' => 'required|string',
            'questions.*.points' => 'required|integer|min:1',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:published_at',
        ]);

        $quiz = Quiz::findOrFail($id);

        if ($quiz->attempts()->exists()) {
            $message = 'This quiz already has student attempts and can no longer be edited, to keep past scores intact. Delete the quiz and create a new one if changes are needed.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        try {
            return DB::transaction(function () use ($request, $id) {
                $quiz = Quiz::findOrFail($id);

                // 1. Update general configurations
                $quiz->update([
                    'title' => $request->title,
                    'time_limit' => $request->time_limit,
                    'published_at' => $request->published_at ?? $quiz->published_at,
                    'expires_at' => $request->expires_at,
                ]);

                // 2. Clear old relational records to update with new structures cleanly
                foreach ($quiz->questions as $oldQuestion) {
                    $oldQuestion->options()->delete();
                }
                $quiz->questions()->delete();

                // 3. Re-populate the questions & choices payload
                foreach ($request->questions as $qData) {
                    $question = $quiz->questions()->create([
                        'question_text' => $qData['text'],
                        'points' => $qData['points'],
                        'type' => $qData['type'],
                    ]);

                    if ($qData['type'] === 'true_false') {
                        foreach ($qData['options'] as $index => $oText) {
                            $question->options()->create([
                                'option_text' => $oText,
                                'is_correct' => $oText === $qData['correct_option'], // compare text, not index
                            ]);
                        }
                    } elseif ($qData['type'] === 'multiple') {
                        foreach ($qData['options'] as $index => $oText) {
                            $question->options()->create([
                                'option_text' => $oText,
                                'is_correct' => $index == $qData['correct_option'],
                            ]);
                        }
                    } elseif ($qData['type'] === 'select_all') {
                        foreach ($qData['options'] as $index => $oText) {
                            $question->options()->create([
                                'option_text' => $oText,
                                'is_correct' => in_array($index, $qData['correct_options'] ?? []),
                            ]);
                        }
                    } elseif ($qData['type'] === 'identification') {
                        $question->options()->create([
                            'option_text' => $qData['answer'],
                            'is_correct' => true,
                        ]);
                    }
                }

                $this->logProfessorActivity(
                    $quiz->subject_id,
                    'Updated the quiz: "' . $quiz->title . '"'
                );

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'quiz' => $quiz]);
                }

                return redirect()->route('professor.classroom.show', $quiz->subject_id)
                    ->with('success', 'Quiz structural updates committed successfully!');
            });
        } catch (\Exception $e) {
            \Log::error('Quiz Update Failed: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error applying quiz changes: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // 1. SECURE LOCK: Check if this user already has a recorded attempt for this quiz
        $alreadyAttempted = \App\Models\QuizAttempt::where('quiz_id', $id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($alreadyAttempted) {
            return redirect()->back()->with('error', 'Access Denied: You have already completed this quiz assessment.');
        }

        // 2. If no attempt exists, load the questions normally
        $quiz = Quiz::with(['labSession', 'questions.options'])->findOrFail($id);

        // 3. Enforce the publish/deadline window here too
        if ($quiz->published_at && now()->lt($quiz->published_at)) {
            return redirect()->back()->with('error', 'This quiz is not yet available.');
        }
        if ($quiz->expires_at && now()->gt($quiz->expires_at)) {
            return redirect()->back()->with('error', 'The deadline for this quiz has passed.');
        }

        return view('student.quizzes.show', compact('quiz'));
    }

    public function listPartial($sessionId)
{
    $session = LabSession::with(['quizzes.questions', 'quizzes.attempts'])->findOrFail($sessionId);
    return view('professor.partials.quiz-list', compact('session'));
}
    private function logProfessorActivity($labSessionId, $content)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'lab_session_id' => $labSessionId,
            'log_type' => 'professor_activity',
            'content' => $content,
        ]);
    }

}