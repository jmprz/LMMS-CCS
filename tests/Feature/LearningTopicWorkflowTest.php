<?php

namespace Tests\Feature;

use App\Models\{LabSession, LearningTopic, Quiz, QuizAttempt, Task, User};
use App\Services\LearningRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Mail};
use Tests\TestCase;

class LearningTopicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private LabSession $class;
    private LearningTopic $topic;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->withoutMiddleware();
        Mail::fake();
        $professor = $this->user('professor');
        $this->student = $this->user('student');
        $this->actingAs($professor);
        $this->class = LabSession::create(['class_code' => 'TEST', 'subject_name' => 'Programming', 'faculty_id' => $professor->id]);
        $this->topic = LearningTopic::create(['name' => 'Loops', 'slug' => 'loops']);
    }

    private function user(string $role): User
    {
        $id = DB::table('users')->insertGetId(['first_name' => 'Test', 'last_name' => $role,
            'name' => 'Test '.$role, 'school_id' => 'TEST-'.$role, 'role' => $role,
            'email' => $role.'@example.test', 'password' => bcrypt('password')]);

        return User::findOrFail($id);
    }

    private function quizPayload(?int $topicId): array
    {
        return ['title' => 'Quiz', 'lab_session_id' => $this->class->id, 'learning_topic_id' => $topicId,
            'time_limit' => 10, 'questions' => [['text' => 'Question', 'type' => 'identification', 'points' => 10, 'answer' => 'Answer']]];
    }

    public function test_quiz_create_edit_and_remove_controlled_topic(): void
    {
        $this->post(route('professor.quizzes.store'), $this->quizPayload($this->topic->id))->assertRedirect();
        $quiz = Quiz::firstOrFail();
        $this->assertSame($this->topic->id, $quiz->learning_topic_id);
        $other = LearningTopic::create(['name' => 'Arrays', 'slug' => 'arrays']);
        $this->putJson(route('professor.quizzes.update', $quiz->id), $this->quizPayload($other->id))->assertOk();
        $this->assertSame($other->id, $quiz->fresh()->learning_topic_id);
        $this->putJson(route('professor.quizzes.update', $quiz->id), $this->quizPayload(null))->assertOk();
        $this->assertNull($quiz->fresh()->learning_topic_id);
        $this->post(route('professor.quizzes.store'), $this->quizPayload(null))->assertRedirect();
        $this->assertNull(Quiz::latest('id')->first()->learning_topic_id);
        $this->postJson(route('professor.quizzes.store'), $this->quizPayload(99999))->assertUnprocessable();
        $this->get(route('professor.quizzes.create', ['session_id' => $this->class->id]))->assertOk()->assertSee('No learning topic assigned');
    }

    private function attemptedQuiz(): array
    {
        $quiz = Quiz::create(['title' => 'Old quiz', 'subject_id' => $this->class->id, 'time_limit' => 10]);
        $attempt = QuizAttempt::create(['user_id' => $this->student->id, 'quiz_id' => $quiz->id,
            'score' => 40, 'total_points' => 100, 'total_questions' => 1, 'is_simulated' => false]);

        return [$quiz, $attempt];
    }

    private function experiment(Quiz $quiz): int
    {
        $experiment = DB::table('research_experiments')->insertGetId(['title' => 'Test experiment', 'status' => 'active', 'weak_topic_threshold' => 75]);
        $group = DB::table('research_groups')->insertGetId(['research_experiment_id' => $experiment, 'lab_session_id' => $this->class->id,
            'group_type' => 'experimental', 'recommendations_enabled' => true]);
        DB::table('research_participants')->insert(['research_group_id' => $group, 'user_id' => $this->student->id, 'consent_status' => 'consented']);
        DB::table('research_assessments')->insert(['research_experiment_id' => $experiment, 'quiz_id' => $quiz->id, 'assessment_type' => 'pretest']);
        $material = DB::table('materials')->insertGetId(['lab_session_id' => $this->class->id, 'title' => 'Loops', 'type' => 'pdf', 'content' => 'test.pdf']);
        DB::table('professor_material_topic')->insert(['material_id' => $material, 'learning_topic_id' => $this->topic->id]);

        return $group;
    }

    public function test_tagging_historical_attempt_does_not_generate_recommendations_or_change_scores(): void
    {
        [$quiz, $attempt] = $this->attemptedQuiz();
        $this->experiment($quiz);
        $before = $attempt->fresh()->getAttributes();
        $mock = \Mockery::mock(LearningRecommendationService::class);
        $mock->shouldNotReceive('generateFromQuizAttempt');
        $this->app->instance(LearningRecommendationService::class, $mock);
        $this->patchJson(route('professor.quizzes.learning-topic.update', $quiz->id), ['learning_topic_id' => $this->topic->id])->assertOk();
        $this->assertSame($this->topic->id, $quiz->fresh()->learning_topic_id);
        $this->assertSame($before, $attempt->fresh()->getAttributes());
        $this->assertDatabaseCount('learning_recommendations', 0);
        $this->get(route('professor.quizzes.edit', $quiz->id))->assertOk()->assertSee('No learning topic assigned');
        $this->patchJson(route('professor.quizzes.learning-topic.update', $quiz->id), ['learning_topic_id' => null])->assertOk();
        $this->assertNull($quiz->fresh()->learning_topic_id);
    }

    public function test_existing_recommendation_experiment_gates_and_idempotency_are_preserved(): void
    {
        [$quiz, $attempt] = $this->attemptedQuiz();
        $group = $this->experiment($quiz);
        $service = app(LearningRecommendationService::class);
        $this->assertSame(0, $service->generateFromQuizAttempt($attempt));
        $quiz->update(['learning_topic_id' => $this->topic->id]);
        $attempt->unsetRelation('quiz');
        DB::table('research_groups')->where('id', $group)->update(['group_type' => 'control']);
        $this->assertSame(0, $service->generateFromQuizAttempt($attempt));
        DB::table('research_groups')->where('id', $group)->update(['group_type' => 'experimental']);
        DB::table('research_participants')->update(['consent_status' => 'pending']);
        $this->assertSame(0, $service->generateFromQuizAttempt($attempt));
        DB::table('research_participants')->update(['consent_status' => 'consented']);
        $attempt->score = 75;
        $this->assertSame(0, $service->generateFromQuizAttempt($attempt));
        $attempt->score = 40;
        $this->assertSame(1, $service->generateFromQuizAttempt($attempt));
        $this->assertSame(0, $service->generateFromQuizAttempt($attempt));
        $this->assertDatabaseCount('learning_recommendations', 1);
    }

    private function taskPayload(?int $topicId): array
    {
        return ['subject_id' => $this->class->id, 'title' => 'Task', 'description' => 'Instructions',
            'learning_topic_id' => $topicId, 'deadline' => '2026-10-20 17:00:00', 'points' => 10,
            'rubric' => ['name' => 'Rubric', 'criteria_json' => '[]']];
    }

    public function test_task_create_edit_remove_and_topic_deletion_preserve_tasks(): void
    {
        $this->postJson(route('professor.tasks.store'), $this->taskPayload($this->topic->id))->assertOk();
        $task = Task::firstOrFail();
        $this->assertSame($this->topic->id, $task->learningTopic->id);
        $other = LearningTopic::create(['name' => 'Arrays', 'slug' => 'arrays']);
        $this->putJson(route('professor.tasks.update', $task->id), $this->taskPayload($other->id))->assertOk()
            ->assertJsonPath('task.learning_topic.name', 'Arrays');
        $this->assertSame($other->id, $task->fresh()->learning_topic_id);
        $this->putJson(route('professor.tasks.update', $task->id), $this->taskPayload(null))->assertOk();
        $this->assertNull($task->fresh()->learning_topic_id);
        $this->postJson(route('professor.tasks.store'), $this->taskPayload(null))->assertOk();
        $this->assertNull(Task::latest('id')->first()->learning_topic_id);
        $this->postJson(route('professor.tasks.store'), $this->taskPayload(99999))->assertUnprocessable();
        $task->update(['learning_topic_id' => $this->topic->id]);
        $this->topic->delete();
        $this->assertNull($task->fresh()->learning_topic_id);
        $this->assertDatabaseCount('tasks', 2);
    }
}
