<?php

namespace Tests\Feature;

use App\Models\LabSession;
use App\Models\SubjectGrade;
use App\Models\User;
use App\Services\OfficialOutcomeService;
use App\Services\StudentRiskDatasetService;
use App\Services\StudentRiskFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfficialOutcomeTest extends TestCase
{
    use RefreshDatabase;

    private User $professor;

    private User $student;

    private LabSession $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->withoutMiddleware([\App\Http\Middleware\EnsureAccountIsActivated::class, \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class]);
        $this->professor = $this->user('professor');
        $this->student = $this->user('student');
        $this->class = LabSession::create(['class_code' => 'OUTCOME', 'subject_name' => 'Test', 'faculty_id' => $this->professor->id]);
        DB::table('class_student')->insert(['user_id' => $this->student->id, 'lab_session_id' => $this->class->id, 'joined_at' => now()->subDay()]);
        $this->actingAs($this->professor);
    }

    private function user(string $role): User
    {
        $key = (string) Str::uuid();

        return User::findOrFail(DB::table('users')->insertGetId(['first_name' => 'Test', 'last_name' => $role, 'name' => 'Test',
            'school_id' => $key, 'email' => $key.'@example.test', 'role' => $role, 'password' => bcrypt('password')]));
    }

    private function preview(string $value = '2.25', ?User $actor = null): object
    {
        return app(OfficialOutcomeService::class)->preview($actor ?? $this->professor, $this->class,
            "student_identifier,final_outcome\n".$this->student->school_id.','.$value."\n", 'Official institutional record', 'grades.csv');
    }

    public function test_professor_csv_preview_then_commit_is_authorized_and_idempotent(): void
    {
        $csv = "student_identifier,final_outcome\n".$this->student->school_id.",2.25\n";
        $response = $this->post(route('outcomes.preview', $this->class->id), ['source' => 'Institution record',
            'csv' => UploadedFile::fake()->createWithContent('grades.csv', $csv)])->assertRedirect();
        $id = DB::table('academic_outcome_imports')->value('id');
        $this->assertDatabaseCount('subject_grades', 0);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('2.25')->assertSee('Commit these official outcomes');
        $this->post(route('outcomes.commit', [$this->class->id, $id]))->assertRedirect();
        $this->post(route('outcomes.commit', [$this->class->id, $id]))->assertRedirect();
        $this->assertDatabaseCount('subject_grades', 1);
        $this->assertDatabaseCount('subject_grade_revisions', 1);
        $this->assertDatabaseHas('subject_grades', ['grading_period' => 'final', 'recorded_by' => $this->professor->id, 'source' => 'Institution record']);
        $this->assertEquals('2.25', SubjectGrade::first()->grade);
    }

    public function test_admin_can_import_and_students_other_professors_cannot_access(): void
    {
        $admin = $this->user('admin');
        $batch = $this->preview(actor: $admin);
        app(OfficialOutcomeService::class)->commit($admin, $this->class, $batch->id);
        $this->actingAs($admin)->get(route('outcomes.index', $this->class->id))->assertOk();
        foreach ([$this->student, $this->user('professor')] as $actor) {
            $this->actingAs($actor)->get(route('outcomes.index', $this->class->id))->assertForbidden();
            $this->post(route('outcomes.preview', $this->class->id), [])->assertForbidden();
            $this->post(route('outcomes.commit', [$this->class->id, $batch->id]))->assertForbidden();
        }
    }

    public function test_invalid_duplicates_and_non_members_block_whole_import(): void
    {
        $outsider = $this->user('student');
        $csv = "student_identifier,final_outcome\n".$this->student->school_id.",2.25\n".$this->student->school_id.",3.00\n".$outsider->school_id.",invalid\n";
        $batch = app(OfficialOutcomeService::class)->preview($this->professor, $this->class, $csv, 'Official', 'grades.csv');
        $rows = json_decode($batch->preview, true);
        $this->assertContains('Duplicate student row.', $rows[1]['errors']);
        $this->assertContains('Unmatched or ambiguous class member.', $rows[2]['errors']);
        $this->assertCount(2, $rows[2]['errors']);
        $this->post(route('outcomes.commit', [$this->class->id, $batch->id]), [], ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertDatabaseCount('subject_grades', 0);
    }

    public function test_correction_preserves_legacy_and_import_history(): void
    {
        SubjectGrade::create(['user_id' => $this->student->id, 'lab_session_id' => $this->class->id, 'grading_period' => 'final', 'grade' => '3.00']);
        $first = $this->preview();
        app(OfficialOutcomeService::class)->commit($this->professor, $this->class, $first->id);
        $response = $this->post(route('outcomes.preview', $this->class->id), ['student_identifier' => $this->student->school_id,
            'final_outcome' => '2.00', 'source' => 'Institutional correction reference'])->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->post(route('outcomes.commit', [$this->class->id, $query['preview']]))->assertRedirect();
        $this->assertDatabaseCount('subject_grades', 1);
        $this->assertEquals(['3.00', '2.25', '2.00'], DB::table('subject_grade_revisions')->orderBy('id')->pluck('grade')->map(fn ($v) => number_format($v, 2))->all());
        $this->assertEquals('2.00', SubjectGrade::first()->grade);
    }

    public function test_stale_preview_membership_change_and_expiry_are_rejected(): void
    {
        $old = $this->preview();
        $new = $this->preview('2.00');
        app(OfficialOutcomeService::class)->commit($this->professor, $this->class, $new->id);
        $this->postJson(route('outcomes.commit', [$this->class->id, $old->id]))->assertStatus(422);
        $batch = $this->preview();
        DB::table('class_student')->delete();
        $this->postJson(route('outcomes.commit', [$this->class->id, $batch->id]))->assertStatus(422);
        DB::table('academic_outcome_imports')->where('id', $batch->id)->update(['expires_at' => now()->subDay()]);
        $this->postJson(route('outcomes.commit', [$this->class->id, $batch->id]))->assertStatus(422);
    }

    public function test_outcomes_join_predictors_by_group_key_without_changing_features(): void
    {
        config(['risk_features.grouping_secret' => 'test-secret']);
        $cutoff = now();
        $features = app(StudentRiskFeatureService::class)->build($this->student->id, $this->class->id, $cutoff);
        $batch = $this->preview();
        app(OfficialOutcomeService::class)->commit($this->professor, $this->class, $batch->id);
        $this->assertEquals($features, app(StudentRiskFeatureService::class)->build($this->student->id, $this->class->id, $cutoff));
        $stream = fopen('php://temp', 'w+');
        app(StudentRiskDatasetService::class)->exportCsv($this->class->id, $cutoff, $stream, false, true);
        rewind($stream);
        $header = fgetcsv($stream, escape: '');
        $row = array_combine($header, fgetcsv($stream, escape: ''));
        fclose($stream);
        $this->assertNotContains('final_outcome', $header);
        $this->assertNotContains('academic_risk_status', $header);
        $filename = 'test-outcomes-'.Str::uuid().'.csv';
        $path = storage_path('app/risk-datasets/'.$filename);
        try {
            $this->artisan('risk:export-outcomes', ['--output' => $filename, '--classes' => (string) $this->class->id])->assertSuccessful();
            $stream = fopen($path, 'r');
            $columns = fgetcsv($stream, escape: '');
            $outcome = array_combine($columns, fgetcsv($stream, escape: ''));
            fclose($stream);
            $this->assertSame(['student_group_key', 'lab_session_id', 'final_outcome', 'recorded_at', 'outcome_revision_id'], $columns);
            $this->assertSame($row['student_group_key'], $outcome['student_group_key']);
            $this->assertSame($row['lab_session_id'], $outcome['lab_session_id']);
            $this->assertEquals(2.25, $outcome['final_outcome']);
            $this->assertStringNotContainsString($this->student->school_id, file_get_contents($path));
            $this->assertStringNotContainsString($this->student->email, file_get_contents($path));
            $this->artisan('risk:export-outcomes', ['--output' => $filename])->assertFailed();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
