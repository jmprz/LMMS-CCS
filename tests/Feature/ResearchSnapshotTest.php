<?php

namespace Tests\Feature;

use App\Models\LabSession;
use App\Models\User;
use App\Services\OfficialOutcomeResearchService;
use App\Services\OfficialOutcomeService;
use App\Services\ResearchSnapshotService;
use App\Services\RiskCollectionStatusService;
use App\Services\RiskFeatureSchema;
use App\Services\RiskTargetService;
use App\Services\StudentRiskDatasetService;
use App\Services\StudentRiskFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResearchSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $professor;

    private array $classes;

    private array $directories = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Carbon::setTestNow('2026-10-03 16:00:00');
        config(['risk_target.passing_grade' => null, 'risk_features.grouping_secret' => 'private-test-secret']);
        $this->professor = $this->user('professor');
        $this->student = $this->user('student');
        $this->classes = [];
        foreach (['TEST-A', 'TEST-B'] as $code) {
            $class = LabSession::create(['class_code' => $code, 'subject_name' => 'Programming', 'faculty_id' => $this->professor->id, 'is_active' => true]);
            DB::table('class_student')->insert(['user_id' => $this->student->id, 'lab_session_id' => $class->id, 'joined_at' => now()->subDay()]);
            $this->classes[] = $class;
        }
    }

    protected function tearDown(): void
    {
        // Delete only the uniquely named directories created by this test.
        foreach ($this->directories as $directory) {
            if (is_dir($directory)) {
                foreach (glob($directory.'/*') as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($directory);
            }
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $key = (string) Str::uuid();

        return User::findOrFail(DB::table('users')->insertGetId(['first_name' => 'Private', 'last_name' => 'Identity', 'name' => 'Private Identity',
            'school_id' => $key, 'email' => $key.'@example.test', 'role' => $role, 'password' => bcrypt('password')]));
    }

    private function snapshotName(): string
    {
        $name = 'test-snapshot-'.Str::uuid();
        $this->directories[] = storage_path('app/risk-datasets/research-snapshots/'.$name.'-'.now()->format('Ymd-His'));

        return $name;
    }

    private function create(?array $ids = null, bool $save = false): array
    {
        return app(ResearchSnapshotService::class)->create($this->snapshotName(), $ids, now(), $save);
    }

    private function csv(string $path): array
    {
        $stream = fopen($path, 'r');
        $header = fgetcsv($stream, escape: '');
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = array_combine($header, $row);
        }
        fclose($stream);

        return [$header, $rows];
    }

    private function databaseFingerprint(): array
    {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $hashes = [];
        foreach ($tables as $table) {
            $rows = DB::table($table->name)->get()->map(fn ($row) => json_encode($row))->sort()->values()->all();
            $hashes[$table->name] = hash('sha256', json_encode($rows));
        }

        return $hashes;
    }

    public function test_package_has_shared_cutoff_v5_counts_and_checksums_without_database_mutations(): void
    {
        $before = $this->databaseFingerprint();
        $result = $this->create();
        $dir = $result['directory'];
        $meta = json_decode(file_get_contents($dir.'/metadata.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertEquals($result['metadata'], $meta);
        $this->assertSame(2, $meta['row_count']);
        $this->assertSame(1, $meta['unique_student_count']);
        $this->assertSame(2, $meta['class_count']);
        $this->assertSame(array_column($this->classes, 'id'), $meta['selected_class_ids']);
        $this->assertSame('v5', $meta['feature_version']);
        $this->assertSame('v5', StudentRiskFeatureService::FEATURE_VERSION);
        $this->assertFalse($meta['features_saved']);
        $this->assertFalse($meta['passing_criterion_configured']);
        $this->assertSame(0, $meta['official_outcome_count']);
        $this->assertSame('2026-10-03 16:00:00', $meta['feature_cutoff_at']);
        $this->assertSame('2026-10-03T16:00:00+08:00', $meta['generated_at']);
        $this->assertCount(5, $meta['filenames_created']);
        foreach ($meta['filenames_created'] as $file) {
            $this->assertFileExists($dir.'/'.$file);
        }
        foreach ($meta['artifact_sha256'] as $file => $hash) {
            $this->assertSame($hash, hash_file('sha256', $dir.'/'.$file));
        }
        [$header, $rows] = $this->csv($dir.'/ml-ready.csv');
        $this->assertSame(app(RiskFeatureSchema::class)->columns(true, true), $header);
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]['student_group_key'], $rows[1]['student_group_key']);
        $this->assertSame(['2026-10-03 16:00:00'], array_values(array_unique(array_column($rows, 'feature_cutoff_at'))));
        foreach ($rows as $row) {
            $this->assertSame('', $row['attendance_rate']);
            $this->assertSame('', $row['activity_duration_seconds']);
            $this->assertSame('{}', $row['topic_task_performance_json']);
            $this->assertSame('v5', $row['feature_version']);
        }
        [, $collectionRows] = $this->csv($dir.'/collection-status.csv');
        $this->assertSame(['2026-10-03 16:00:00'], array_values(array_unique(array_column($collectionRows, 'cutoff'))));
        $collection = json_decode(file_get_contents($dir.'/collection-status.json'), true);
        $this->assertSame($meta['feature_cutoff_at'], $collection['cutoff']);
        $this->assertStringContainsString($meta['feature_cutoff_at'], file_get_contents($dir.'/label-readiness.txt'));
        $this->assertSame($before, $this->databaseFingerprint());
    }

    public function test_all_artifacts_and_console_exclude_pii_and_raw_ids(): void
    {
        $name = $this->snapshotName();
        $this->artisan('risk:research-snapshot', ['--name' => $name])->doesntExpectOutputToContain('Private Identity')
            ->doesntExpectOutputToContain($this->student->school_id)->doesntExpectOutputToContain($this->student->email)->assertSuccessful();
        $dir = end($this->directories);
        foreach (glob($dir.'/*') as $file) {
            $text = file_get_contents($file);
            foreach ([$this->student->school_id, $this->student->email, 'Private Identity', 'user_id', 'private-test-secret'] as $private) {
                $this->assertStringNotContainsString($private, $text);
            }
        }
        [$header] = $this->csv($dir.'/ml-ready.csv');
        foreach (['current_academic_grade', 'violation_count', 'final_outcome', 'academic_risk_status'] as $excluded) {
            $this->assertNotContains($excluded, $header);
        }
    }

    public function test_explicit_classes_are_deduplicated_and_scope_all_reports(): void
    {
        $name = $this->snapshotName();
        $id = $this->classes[1]->id;
        $this->artisan('risk:research-snapshot', ['--name' => $name, '--classes' => "$id,$id"])->assertSuccessful();
        $dir = end($this->directories);
        $meta = json_decode(file_get_contents($dir.'/metadata.json'), true);
        $this->assertSame([$id], $meta['selected_class_ids']);
        $this->assertSame(1, $meta['row_count']);
        $collection = json_decode(file_get_contents($dir.'/collection-status.json'), true);
        $this->assertSame([$id], array_column($collection['classes'], 'lab_session_id'));
        $this->assertStringContainsString('feature_classes: 1', file_get_contents($dir.'/label-readiness.txt'));
    }

    public function test_save_features_requires_explicit_flag_and_preserves_shared_cutoff(): void
    {
        $name = $this->snapshotName();
        $this->artisan('risk:research-snapshot', ['--name' => $name, '--save-features' => true])->assertSuccessful();
        $this->assertDatabaseCount('student_feature_snapshots', 2);
        foreach (DB::table('student_feature_snapshots')->get() as $row) {
            $this->assertSame('2026-10-03 16:00:00', $row->feature_cutoff_at);
            $this->assertSame('v5', $row->feature_version);
        }
        $meta = json_decode(file_get_contents(end($this->directories).'/metadata.json'), true);
        $this->assertTrue($meta['features_saved']);
        $result = $this->create(save: true);
        $this->assertDatabaseCount('student_feature_snapshots', 2); // Same cutoff reuses existing records.
        $this->assertTrue($result['metadata']['features_saved']);
    }

    public function test_clock_advancing_during_export_does_not_change_cutoff(): void
    {
        $real = app(StudentRiskDatasetService::class);
        $mock = \Mockery::mock(StudentRiskDatasetService::class);
        $mock->shouldReceive('exportClassesCsv')->once()->andReturnUsing(function (...$args) use ($real) {
            $result = $real->exportClassesCsv(...$args);
            Carbon::setTestNow('2026-10-03 16:05:00');

            return $result;
        });
        $this->app->instance(StudentRiskDatasetService::class, $mock);
        $result = $this->create();
        $meta = $result['metadata'];
        $collection = json_decode(file_get_contents($result['directory'].'/collection-status.json'), true);
        $this->assertSame('2026-10-03 16:00:00', $meta['feature_cutoff_at']);
        $this->assertSame($meta['feature_cutoff_at'], $collection['cutoff']);
        $this->assertStringContainsString('2026-10-03 16:00:00', file_get_contents($result['directory'].'/label-readiness.txt'));
        $this->assertSame('2026-10-03T16:00:00+08:00', $meta['generated_at']);
    }

    public function test_safe_names_and_collision_fail_without_overwrite(): void
    {
        $raw = 'Test Week One '.Str::uuid();
        $slug = Str::slug($raw);
        $dir = storage_path('app/risk-datasets/research-snapshots/'.$slug.'-20261003-160000');
        $this->directories[] = $dir;
        $result = app(ResearchSnapshotService::class)->create($raw, null, now());
        $this->assertSame($dir, $result['directory']);
        $this->assertSame($slug, $result['metadata']['snapshot_name']);
        $before = hash_file('sha256', $dir.'/metadata.json');
        $this->artisan('risk:research-snapshot', ['--name' => $raw, '--save-features' => true])->expectsOutputToContain('nothing was overwritten')->assertFailed();
        $this->assertSame($before, hash_file('sha256', $dir.'/metadata.json'));
        $this->assertDatabaseCount('student_feature_snapshots', 0);
        foreach (['../outside', 'nested/name', 'nested\\name', "bad\nname", '!!!', str_repeat('x', 81)] as $unsafe) {
            $this->artisan('risk:research-snapshot', ['--name' => $unsafe])->assertFailed();
        }
        $this->artisan('risk:research-snapshot')->assertFailed();
        $this->artisan('risk:research-snapshot', ['--name' => $this->snapshotName(), '--classes' => '1,,2'])->assertFailed();
        $this->artisan('risk:research-snapshot', ['--name' => $this->snapshotName(), '--classes' => '999999'])->assertFailed();
    }

    public function test_failure_rolls_back_opt_in_writes_and_removes_only_new_package(): void
    {
        $name = $this->snapshotName();
        $mock = \Mockery::mock(RiskCollectionStatusService::class);
        $mock->shouldReceive('report')->once()->andThrow(new \RuntimeException('Report unavailable.'));
        $this->app->instance(RiskCollectionStatusService::class, $mock);
        $before = $this->databaseFingerprint();
        $this->artisan('risk:research-snapshot', ['--name' => $name, '--save-features' => true])->assertFailed();
        $this->assertDirectoryDoesNotExist(end($this->directories));
        $this->assertSame($before, $this->databaseFingerprint());
    }

    public function test_empty_auto_selection_creates_header_only_package(): void
    {
        DB::table('lab_sessions')->update(['is_active' => false]);
        $result = $this->create();
        $this->assertSame(0, $result['metadata']['row_count']);
        $this->assertSame(0, $result['metadata']['unique_student_count']);
        $this->assertSame([], $result['metadata']['selected_class_ids']);
        [, $rows] = $this->csv($result['directory'].'/ml-ready.csv');
        $this->assertSame([], $rows);
    }

    public function test_outcome_scope_as_of_revisions_and_no_label_calls_even_with_configured_criterion(): void
    {
        config(['risk_target.passing_grade' => '3.00']);
        $outcomes = app(OfficialOutcomeService::class);
        Carbon::setTestNow('2026-10-03 15:00:00');
        foreach ($this->classes as $class) {
            $batch = $outcomes->preview($this->professor, $class, "student_identifier,final_outcome\n".$this->student->school_id.",2.50\n", 'Official test record', 'test.csv');
            $outcomes->commit($this->professor, $class, $batch->id);
        }
        Carbon::setTestNow('2026-10-03 17:00:00');
        $batch = $outcomes->preview($this->professor, $this->classes[0], "student_identifier,final_outcome\n".$this->student->school_id.",2.00\n", 'Correction test', 'test.csv');
        $outcomes->commit($this->professor, $this->classes[0], $batch->id);
        Carbon::setTestNow('2026-10-03 16:00:00');
        $rows = app(OfficialOutcomeResearchService::class)->rows([$this->classes[0]->id], now());
        $this->assertCount(1, $rows);
        $this->assertEquals(2.50, $rows[0]['final_outcome']);
        $target = \Mockery::mock(RiskTargetService::class)->makePartial();
        $target->shouldNotReceive('label');
        $this->app->instance(RiskTargetService::class, $target);
        $before = $this->databaseFingerprint();
        $result = $this->create([$this->classes[0]->id]);
        $this->assertSame(1, $result['metadata']['official_outcome_count']);
        $this->assertSame(1, $result['metadata']['official_outcomes_with_valid_timestamp']);
        $this->assertTrue($result['metadata']['passing_criterion_configured']);
        $text = file_get_contents($result['directory'].'/label-readiness.txt');
        $this->assertStringContainsString('imported_revision_count: 1', $text);
        $this->assertStringContainsString('no labels or target distributions computed', $text);
        $this->assertCount(5, glob($result['directory'].'/*'));
        $this->assertSame($before, $this->databaseFingerprint());
    }
}
