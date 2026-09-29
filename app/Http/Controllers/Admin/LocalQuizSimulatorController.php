<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabSession;
use App\Models\LearningRecommendation;
use App\Models\LearningTopic;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptDetail;
use App\Models\ResearchAssessment;
use App\Models\ResearchExperiment;
use App\Models\ResearchGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocalQuizSimulatorController extends Controller
{
    public function index(Request $request)
    {
        $this->guardLocal();

        $experiments = ResearchExperiment::query()
            ->with(['groups.labSession.faculty', 'groups.participants'])
            ->latest()
            ->get();

        $topics = LearningTopic::query()->orderBy('name')->get();

        $recentPairs = Quiz::query()
            ->with(['labSession.faculty', 'learningTopic'])
            ->where('title', 'like', '[LOCAL TEST] %')
            ->latest()
            ->take(20)
            ->get()
            ->groupBy(fn (Quiz $quiz) => preg_replace('/ \[Section .*\]$/', '', $quiz->title));

        return view('admin.local-quiz-simulator.index', compact('experiments', 'topics', 'recentPairs'));
    }

    public function store(Request $request)
    {
        $this->guardLocal();

        $validated = $request->validate([
            'experiment_id' => ['required', Rule::exists('research_experiments', 'id')],
            'group_ids' => ['required', 'array', 'size:2'],
            'group_ids.*' => ['required', 'integer', Rule::exists('research_groups', 'id')],
            'title' => ['required', 'string', 'max:180'],
            'learning_topic_id' => ['required', 'integer', Rule::exists('learning_topics', 'id')],
            'time_limit' => ['required', 'integer', 'min:1', 'max:240'],
            'assessment_type' => ['required', Rule::in(['pretest', 'posttest'])],
            'questions' => ['required', 'array', 'min:1', 'max:100'],
            'questions.*.text' => ['required', 'string', 'max:2000'],
            'questions.*.type' => ['required', Rule::in(['multiple', 'true_false', 'select_all'])],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:8'],
            'questions.*.options.*' => ['required', 'string', 'max:1000'],
            'questions.*.correct_option' => ['nullable'],
            'questions.*.correct_options' => ['nullable', 'array'],
        ]);

        $experiment = ResearchExperiment::with('groups.labSession')->findOrFail($validated['experiment_id']);
        $groups = ResearchGroup::with('labSession')
            ->where('research_experiment_id', $experiment->id)
            ->whereIn('id', $validated['group_ids'])
            ->get();

        if ($groups->count() !== 2) {
            return back()->withErrors(['group_ids' => 'Choose exactly two groups that belong to the selected experiment.'])->withInput();
        }

        $sessionA = $groups[0]->labSession;
        $sessionB = $groups[1]->labSession;

        if (!$sessionA || !$sessionB) {
            return back()->withErrors(['group_ids' => 'One of the selected classes is no longer available.'])->withInput();
        }

        if ($sessionA->faculty_id !== $sessionB->faculty_id || $sessionA->subject_name !== $sessionB->subject_name) {
            return back()->withErrors([
                'group_ids' => 'For this simulator, both classes must use the same professor and exact subject name.',
            ])->withInput();
        }

        $topic = LearningTopic::findOrFail($validated['learning_topic_id']);

        $quizzes = DB::transaction(function () use ($validated, $groups, $topic, $experiment) {
            $created = collect();

            foreach ($groups as $group) {
                $session = $group->labSession;
                $sectionLabel = trim(($session->program ?? '') . ' ' . ($session->year_level ?? '') . ($session->section ?? ''));

                $quiz = Quiz::create([
                    'title' => '[LOCAL TEST] ' . $validated['title'] . ' [Section ' . $sectionLabel . ']',
                    'topic' => $topic->name,
                    'learning_topic_id' => $topic->id,
                    'subject_id' => $session->id,
                    'time_limit' => $validated['time_limit'],
                    'published_at' => now(),
                    'expires_at' => now()->addDays(7),
                ]);

                foreach ($validated['questions'] as $qData) {
                    $question = $quiz->questions()->create([
                        'question_text' => $qData['text'],
                        'points' => $qData['points'],
                        'type' => $qData['type'],
                    ]);

                    if ($qData['type'] === 'multiple') {
                        foreach ($qData['options'] as $index => $text) {
                            $question->options()->create([
                                'option_text' => $text,
                                'is_correct' => (string) $index === (string) ($qData['correct_option'] ?? ''),
                            ]);
                        }
                    } elseif ($qData['type'] === 'true_false') {
                        foreach ($qData['options'] as $text) {
                            $question->options()->create([
                                'option_text' => $text,
                                'is_correct' => strtolower(trim($text)) === strtolower(trim((string) ($qData['correct_option'] ?? ''))),
                            ]);
                        }
                    } else {
                        $correctIndexes = array_map('strval', $qData['correct_options'] ?? []);
                        foreach ($qData['options'] as $index => $text) {
                            $question->options()->create([
                                'option_text' => $text,
                                'is_correct' => in_array((string) $index, $correctIndexes, true),
                            ]);
                        }
                    }
                }

                ResearchAssessment::updateOrCreate(
                    [
                        'research_experiment_id' => $experiment->id,
                        'quiz_id' => $quiz->id,
                    ],
                    [
                        'assessment_type' => $validated['assessment_type'],
                        'assessment_version' => 'LOCAL-SIM',
                    ]
                );

                $created->push($quiz);
            }

            return $created;
        });

        return redirect()
            ->route('admin.local-quiz-simulator.index', ['quiz_ids' => $quizzes->pluck('id')->implode(',')])
            ->with('success', 'Local test quiz created for both sections and mapped to the experiment.');
    }

    public function simulate(Request $request)
    {
        $this->guardLocal();

        $validated = $request->validate([
            'quiz_ids' => ['required', 'array', 'size:2'],
            'quiz_ids.*' => ['required', 'integer', Rule::exists('quizzes', 'id')],
            'performance_mode' => ['required', Rule::in(['random_answers', 'mixed', 'mostly_low', 'mostly_high'])],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        $quizzes = Quiz::with(['questions.options', 'labSession.students', 'learningTopic'])
            ->whereIn('id', $validated['quiz_ids'])
            ->get();

        if ($quizzes->count() !== 2 || $quizzes->contains(fn (Quiz $q) => !str_starts_with($q->title, '[LOCAL TEST] '))) {
            return back()->withErrors(['quiz_ids' => 'Only paired quizzes created by the local simulator may be simulated.']);
        }

        $createdAttempts = 0;
        $skippedAttempts = 0;
        $recommendationsBefore = LearningRecommendation::count();

        foreach ($quizzes as $quiz) {
            $students = $quiz->labSession?->students ?? collect();

            foreach ($students as $student) {
                $existing = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('user_id', $student->id)
                    ->first();

                if ($existing && empty($validated['replace_existing'])) {
                    $skippedAttempts++;
                    continue;
                }

                DB::transaction(function () use ($quiz, $student, $existing, $validated, &$createdAttempts) {
                    if ($existing) {
                        LearningRecommendation::where('quiz_attempt_id', $existing->id)->delete();
                        $existing->details()->delete();
                        $existing->delete();
                    }

                    $studentProbability = $this->studentCorrectProbability($validated['performance_mode']);
                    $totalPoints = (float) $quiz->questions->sum('points');
                    $score = 0.0;
                    $details = [];

                    foreach ($quiz->questions as $question) {
                        $isCorrect = $validated['performance_mode'] === 'random_answers'
                            ? $this->naturalRandomCorrect($question)
                            : $this->roll($studentProbability);

                        $pointsPossible = (float) $question->points;
                        $pointsEarned = $isCorrect ? $pointsPossible : 0.0;
                        $score += $pointsEarned;

                        $details[] = [
                            'question_id' => $question->id,
                            'is_correct' => $isCorrect,
                            'points_earned' => $pointsEarned,
                            'points_possible' => $pointsPossible,
                        ];
                    }

                    $attempt = QuizAttempt::create([
                        'user_id' => $student->id,
                        'quiz_id' => $quiz->id,
                        'score' => $score,
                        'total_questions' => $quiz->questions->count(),
                        'total_points' => $totalPoints,
                        'time_spent' => random_int(120, max(121, $quiz->time_limit * 60)),
                        'is_simulated' => true,
                    ]);

                    foreach ($details as $detail) {
                        QuizAttemptDetail::create($detail + ['quiz_attempt_id' => $attempt->id]);
                    }

                    // Exercise the real recommendation pipeline, if installed.
                    if (class_exists(\App\Services\LearningRecommendationService::class)) {
                        app(\App\Services\LearningRecommendationService::class)->generateFromQuizAttempt($attempt);
                    }

                    $createdAttempts++;
                });
            }
        }

        $recommendationsCreated = max(0, LearningRecommendation::count() - $recommendationsBefore);

        return back()->with('success', "Simulation complete: {$createdAttempts} synthetic attempts created, {$skippedAttempts} skipped, {$recommendationsCreated} recommendations generated.");
    }

    public function reset(Request $request)
    {
        $this->guardLocal();

        $validated = $request->validate([
            'quiz_ids' => ['required', 'array', 'size:2'],
            'quiz_ids.*' => ['required', 'integer', Rule::exists('quizzes', 'id')],
        ]);

        $attempts = QuizAttempt::whereIn('quiz_id', $validated['quiz_ids'])
            ->where('is_simulated', true)
            ->get();

        DB::transaction(function () use ($attempts) {
            $ids = $attempts->pluck('id');
            LearningRecommendation::whereIn('quiz_attempt_id', $ids)->delete();
            QuizAttemptDetail::whereIn('quiz_attempt_id', $ids)->delete();
            QuizAttempt::whereIn('id', $ids)->delete();
        });

        return back()->with('success', 'Synthetic attempts and their generated recommendations were removed. The test quizzes were kept.');
    }

    private function guardLocal(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        abort_unless(auth()->check() && auth()->user()->role === 'admin', 403);
    }

    private function studentCorrectProbability(string $mode): float
    {
        return match ($mode) {
            'mostly_low' => random_int(25, 55) / 100,
            'mostly_high' => random_int(75, 95) / 100,
            'mixed' => random_int(30, 95) / 100,
            default => 0.25,
        };
    }

    private function naturalRandomCorrect($question): bool
    {
        $optionCount = max(1, $question->options->count());

        $probability = match ($question->type) {
            'true_false' => 0.50,
            'multiple' => 1 / $optionCount,
            'select_all' => 1 / (2 ** min($optionCount, 12)),
            default => 0.10,
        };

        return $this->roll($probability);
    }

    private function roll(float $probability): bool
    {
        $probability = max(0, min(1, $probability));
        return random_int(1, 10000) <= (int) round($probability * 10000);
    }
}
