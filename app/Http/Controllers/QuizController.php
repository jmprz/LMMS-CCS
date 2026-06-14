<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\LabSession;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

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
                if ($qData['type'] === 'multiple' || $qData['type'] === 'true_false') {
                    foreach ($qData['options'] as $index => $oText) {
                        $question->options()->create([
                            'option_text' => $oText,
                            // For true_false, correct_option is usually a string 'True' or 'False'
                            // For multiple, it is the index (0, 1, 2...)
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

        // 4. Send the scoring context straight down to the view template
        return view('student.quizzes.attempt', compact('quiz', 'completed', 'studentScore'));
    }

    public function submit(Request $request, $quizId)
    {
        try {
            return \DB::transaction(function () use ($request, $quizId) {
                $quiz = Quiz::with('questions.options')->findOrFail($quizId);

                $totalQuestions = $quiz->questions->count();
                $score = 0;
                $details = [];

                $userAnswers = collect($request->input('answers', []));

                foreach ($quiz->questions as $question) {
                    $userAnswer = $userAnswers->get($question->id);
                    $isCorrect = false;

                    // 1. Get ALL correct option texts
                    $correctOptions = $question->options
                        ->where('is_correct', true)
                        ->pluck('option_text')
                        ->map(fn($text) => strtolower(trim($text)))
                        ->toArray();

                    // 2. Normalize user input
                    // If it's a string, make it an array. If array, trim/lower all items.
                    $userAnswerArray = is_array($userAnswer) ? $userAnswer : [$userAnswer];
                    $cleanUser = array_map(fn($ans) => strtolower(trim($ans)), array_filter($userAnswerArray));

                    // 3. Compare (Handles both single radio and multi-select checkbox)
                    if (count($cleanUser) > 0) {
                        sort($cleanUser);
                        sort($correctOptions);

                        if ($cleanUser === $correctOptions) {
                            $isCorrect = true;
                            $score++;
                        }
                    }

                    $details[] = [
                        'question_id' => $question->id,
                        'is_correct' => $isCorrect
                    ];
                }

                // 4. Create Attempt & STOPWATCH DURATION CALCULATION
                $startTime = \Carbon\Carbon::parse($request->input('start_time'));

                // 🟢 FIXED: Convert BOTH times to UNIX raw timestamps (pure total seconds since 1970)
                // This shields calculations against negative timezone/clock offsets entirely.
                $timeSpent = abs(now()->getTimestamp() - $startTime->getTimestamp());

                $attempt = \App\Models\QuizAttempt::create([
                    'user_id' => auth()->id(),
                    'quiz_id' => $quiz->id,
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'time_spent' => $timeSpent,
                ]);

                // 5. Save Details
                foreach ($details as $detail) {
                    \App\Models\QuizAttemptDetail::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $detail['question_id'],
                        'is_correct' => $detail['is_correct'],
                    ]);
                }

                // 6. 🟢 FIXED: Create Timeline Log row with the actual dynamic duration included!
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'log_type' => 'quiz',
                    'content' => "Student completed quiz assessment: \"" . $quiz->title . "\"",
                    'lab_session_id' => $quiz->subject_id, // Maps to your subject_id column
                    'duration_seconds' => $timeSpent,        // Reflects real stopwatch metrics
                ]);

                return response()->json([
                    'success' => true,
                    'score' => $score,
                    'total' => $totalQuestions
                ]);
            });
        } catch (\Exception $e) {
            // Return error JSON so the frontend doesn't hang on "SUBMITTING..."
            return response()->json([
                'success' => false,
                'message' => 'Error processing quiz: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);
        $quiz->delete(); // This removes the quiz and related attempts if cascade is on

        return redirect()->back()->with('success', 'Quiz deleted successfully!');
    }

    public function edit($id)
    {
        // Eager load questions and options to populate your form fields cleanly
        $quiz = Quiz::with('questions.options')->findOrFail($id);

        // Pass the quiz context to your modification view
        return view('professor.quizzes.edit', compact('quiz'));
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

                    if ($qData['type'] === 'multiple' || $qData['type'] === 'true_false') {
                        foreach ($qData['options'] as $index => $oText) {
                            $question->options()->create([
                                'option_text' => $oText,
                                'is_correct' => $index == ($qData['correct_option'] ?? null),
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

                return redirect()->route('professor.classroom.show', $quiz->subject_id)
                    ->with('success', 'Quiz structural updates committed successfully!');
            });
        } catch (\Exception $e) {
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

        return view('student.quizzes.show', compact('quiz'));
    }
}