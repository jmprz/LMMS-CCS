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

            return redirect()->route('professor.classroom.show', $request->lab_session_id)
                ->with('success', 'Quiz created successfully!');
        });
    }
    // app/Http/Controllers/Student/QuizController.php

    public function attempt($id)
    {
        // Load the quiz with its questions
        $quiz = Quiz::with('questions')->findOrFail($id);

        return view('student.quizzes.attempt', compact('quiz'));
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

                // 4. Create Attempt
                $startTime = \Carbon\Carbon::parse($request->input('start_time'));
                $timeSpent = abs(now()->diffInSeconds($startTime));

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

    public function show($id)
    {
        // Eager load labSession to get the subject_name
        $quiz = Quiz::with(['labSession', 'questions.options'])->findOrFail($id);

        return view('student.quizzes.show', compact('quiz'));
    }
}