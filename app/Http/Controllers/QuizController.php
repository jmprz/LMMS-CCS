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
                } 
                elseif ($qData['type'] === 'select_all') {
                    foreach ($qData['options'] as $index => $oText) {
                        $question->options()->create([
                            'option_text' => $oText,
                            'is_correct' => in_array($index, $qData['correct_options'] ?? []),
                        ]);
                    }
                } 
                elseif ($qData['type'] === 'identification') {
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

public function submit(Request $request, $id)
{
    // 1. Eager load labSession so we have the ID for the redirect
    $quiz = Quiz::with(['questions.options', 'labSession'])->findOrFail($id);
    
    $submittedAnswers = $request->input('answers', []);
    $score = 0;
    $totalQuestions = $quiz->questions->count();

    foreach ($quiz->questions as $question) {
        $userAnswer = $submittedAnswers[$question->id] ?? null;

        if ($question->type === 'select_all') {
            $correctOptionTexts = $question->options->where('is_correct', true)
                ->pluck('option_text')
                ->map(fn($t) => strtolower(trim($t)))
                ->toArray();
            
            $userAnswerArray = is_array($userAnswer) 
                ? array_map(fn($t) => strtolower(trim($t)), $userAnswer) 
                : [];

            sort($correctOptionTexts);
            sort($userAnswerArray);

            if ($correctOptionTexts === $userAnswerArray && !empty($userAnswerArray)) {
                $score++;
            }
        } 
        else {
            $correctOption = $question->options->where('is_correct', true)->first();
            
            if ($correctOption && $userAnswer && !is_array($userAnswer)) {
                $cleanUser = strtolower(trim($userAnswer));
                $cleanCorrect = strtolower(trim($correctOption->option_text));

                if ($cleanUser === $cleanCorrect) {
                    $score++;
                }
            }
        }
    }

    // Time Calculation
    $startTime = \Carbon\Carbon::parse($request->input('start_time'));
    $timeSpentInSeconds = abs(now()->diffInSeconds($startTime));

    \App\Models\QuizAttempt::create([
        'user_id' => auth()->id(),
        'quiz_id' => $quiz->id,
        'score' => $score,
        'total_questions' => $totalQuestions,
        'time_spent' => $timeSpentInSeconds,
    ]);

    // 2. REDIRECT TO SUBJECT/LAB SESSION
    return redirect()->route('student.subject', $quiz->subject_id)
    ->with('success', "Quiz submitted! Score: $score/$totalQuestions");
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