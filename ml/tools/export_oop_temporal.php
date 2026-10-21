<?php

/** Local-only approved OOP temporal cohort. Read-only SQL; exclusive new file output. */
use App\Services\ResearchOutcomePolicyService;
use App\Services\RiskFeatureSchema;
use App\Services\StudentRiskFeatureService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$options = getopt('', ['output:', 'check-only']);
$created = [];

try {
    $filename = $options['output'] ?? 'oop-temporal-labeled-v1.csv';
    if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.csv\z/', $filename)) {
        throw new RuntimeException('Use a new CSV filename, without directories.');
    }
    $output = storage_path('app/risk-datasets/'.$filename);
    $stem = substr($output, 0, -4);
    $paths = [$output, $stem.'.features.csv', $stem.'.schema.json', $stem.'.provenance.json', $stem.'.evidence.json'];
    if (array_filter($paths, 'file_exists')) {
        throw new RuntimeException('Output collision; existing artifacts are never overwritten.');
    }
    $connection = DB::connection();
    if ($connection->getConfig('host') !== '127.0.0.1' || (string) $connection->getConfig('port') !== '3306' ||
        $connection->getConfig('database') !== 'lmms_ccs_db_prod_1018') {
        throw new RuntimeException('Stop: the approved local database target is not confirmed.');
    }
    $pdo = $connection->getPdo();
    $datadir = $pdo->query('SELECT @@datadir')->fetchColumn();
    if (strtolower(str_replace('\\', '/', $datadir)) !== 'c:/xampp/mysql/data/') {
        throw new RuntimeException('Stop: database is not the intended local XAMPP instance.');
    }
    $query = function (string $sql, array $arguments = []) use ($pdo): array {
        $statement = $pdo->prepare($sql);
        $statement->execute($arguments);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    };
    $cutoff = '2026-10-15 17:59:59';
    $horizon = now()->format('Y-m-d H:i:s');
    $lateFeature = storage_path('app/risk-datasets/all-classes-20261020-223311-v5-ml-ready.csv');
    $lateHash = hash_file('sha256', $lateFeature);
    if ($lateHash !== '11a185bc82b1eac5910b3376a167ea4f7809f013bf07a796d6636888ae3ea40c') {
        throw new RuntimeException('Stop: preserved source feature artifact changed.');
    }
    $stream = fopen($lateFeature, 'rb');
    $header = fgetcsv($stream, escape: '');
    $originalPairs = [];
    while (($cells = fgetcsv($stream, escape: '')) !== false) {
        if ($cells === [null]) {
            continue;
        }
        $row = array_combine($header, $cells);
        $originalPairs[$row['student_group_key'].':'.$row['lab_session_id']] = true;
    }
    fclose($stream);
    $policy = ['policy_version' => ResearchOutcomePolicyService::VERSION, 'enabled' => true,
        'approval_reference' => 'USER-APPROVED-OOP-MAPPED-CONSENTED-ROSTER-20261021',
        'performance_threshold' => 70, 'comparison_direction' => 'gte', 'minimum_eligible_opportunities' => 1,
        'no_participation_rule' => 'at_risk', 'aggregation' => 'equal_opportunity_percentage',
        'outcome_start' => '2026-10-15 18:00:00', 'outcome_end' => '2026-10-17 07:59:00', 'assessment_types' => ['quiz']];
    $specs = [
        4 => ['code' => 'OOPRG_2B', 'quiz' => 11, 'group' => 'control', 'roster' => 55, 'completed' => 43,
            'open' => '2026-10-15 18:00:00', 'close' => '2026-10-16 01:00:00'],
        8 => ['code' => 'OOPRG_2A', 'quiz' => 9, 'group' => 'experimental', 'roster' => 53, 'completed' => 29,
            'open' => '2026-10-16 20:00:00', 'close' => '2026-10-17 07:59:00'],
    ];
    $schema = app(RiskFeatureSchema::class);
    $features = app(StudentRiskFeatureService::class);
    $evaluator = app(ResearchOutcomePolicyService::class);
    $rows = $featureRows = $contexts = $evidenceRows = $classes = [];
    $predictorRegistry = [];
    $reasons = ['nonparticipation' => 0, 'below_threshold' => 0, 'meets_threshold' => 0];
    Illuminate\Support\Facades\Log::swap(new Psr\Log\NullLogger());
    $pdo->exec('START TRANSACTION READ ONLY');
    try {
        // Any possible exception is a stop-for-review gate, never an automatic risk label.
        $exceptionActivity = (int) $query("SELECT COUNT(*) AS n FROM activity_logs WHERE lab_session_id IN (4,8)
            AND (log_type REGEXP 'exempt|excus|cancel|withdraw' OR LOWER(content) REGEXP 'exempt|excus|cancel|withdraw')")[0]['n'];
        $attendanceExceptions = (int) $query("SELECT COUNT(*) AS n FROM attendance_session_students r JOIN attendance_sessions s ON s.id=r.attendance_session_id
            WHERE s.lab_session_id IN (4,8) AND (r.status='excused' OR s.status='cancelled')")[0]['n'];
        $protocolExceptions = (int) $query("SELECT COUNT(*) AS n FROM research_experiments WHERE id=1
            AND LOWER(COALESCE(description,'')) REGEXP 'exempt|excus|cancel|withdraw'")[0]['n'];
        $specialColumns = $query("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
            AND (COLUMN_NAME REGEXP 'exempt|excus|withdraw' OR TABLE_NAME REGEXP 'exempt|excus|withdraw')");
        if ($exceptionActivity || $attendanceExceptions || $protocolExceptions || $specialColumns) {
            throw new RuntimeException('Stop: potential exemption metadata requires individual review and exclusion reconciliation.');
        }
        $experiment = $query('SELECT id,status,intervention_starts_at,intervention_ends_at,created_at,updated_at FROM research_experiments WHERE id=1');
        if (count($experiment) !== 1 || $experiment[0]['status'] !== 'active' || $experiment[0]['created_at'] > $cutoff ||
            $experiment[0]['updated_at'] > $cutoff || $experiment[0]['intervention_starts_at'] > $policy['outcome_start'] ||
            $experiment[0]['intervention_ends_at'] < $policy['outcome_end']) {
            throw new RuntimeException('Stop: experiment chronology/status changed.');
        }
        foreach ($specs as $classId => $spec) {
            $classRecord = $query('SELECT class_code,is_active FROM lab_sessions WHERE id=?', [$classId]);
            if (count($classRecord) !== 1 || $classRecord[0]['class_code'] !== $spec['code'] || ! $classRecord[0]['is_active']) {
                throw new RuntimeException('Stop: approved class scope changed.');
            }
            $maps = $query("SELECT q.*,ra.created_at AS mapped_at,rg.id AS research_group_id,rg.group_type,
                rg.created_at AS group_created_at,rg.updated_at AS group_updated_at
                FROM quizzes q JOIN research_assessments ra ON ra.quiz_id=q.id
                JOIN research_groups rg ON rg.research_experiment_id=ra.research_experiment_id AND rg.lab_session_id=q.subject_id
                WHERE q.id=? AND ra.assessment_type='posttest' AND ra.research_experiment_id=1", [$spec['quiz']]);
            if (count($maps) !== 1) {
                throw new RuntimeException('Stop: ambiguous posttest mapping.');
            }
            $map = $maps[0];
            if ((int) $map['subject_id'] !== $classId || $map['group_type'] !== $spec['group'] || $map['mapped_at'] > $cutoff ||
                $map['group_created_at'] > $cutoff || $map['group_updated_at'] > $cutoff ||
                $map['published_at'] !== $spec['open'] || $map['expires_at'] !== $spec['close'] || $map['expires_at'] > $horizon) {
                throw new RuntimeException('Stop: class, roster or outcome window changed.');
            }
            $roster = $query("SELECT rp.user_id,rp.created_at AS assigned_at,rp.consented_at
                FROM research_participants rp JOIN users u ON u.id=rp.user_id
                WHERE rp.research_group_id=? AND rp.consent_status='consented' AND u.role='student'
                AND rp.created_at<=? AND rp.consented_at IS NOT NULL AND rp.consented_at<=?
                AND EXISTS(SELECT 1 FROM class_student cs WHERE cs.user_id=rp.user_id AND cs.lab_session_id=? AND cs.joined_at<=?) ORDER BY rp.user_id",
                [$map['research_group_id'], $cutoff, $cutoff, $classId, $cutoff]);
            if (count($roster) !== $spec['roster'] || count(array_unique(array_column($roster, 'user_id'))) !== count($roster)) {
                throw new RuntimeException('Stop: mapped/consented roster differs from the approved cohort.');
            }
            foreach ($query('SELECT id FROM quizzes WHERE subject_id=? AND created_at<=?', [$classId, $cutoff]) as $op) {
                // Includes descriptive pre-created content; later posttests are removed below only if no early attempts exist.
                if ((int) $op['id'] !== $spec['quiz']) {
                    $predictorRegistry[] = 'quiz:'.$classId.':'.$op['id'];
                }
            }
            foreach ($query('SELECT id FROM tasks WHERE subject_id=? AND deadline<=? AND created_at<=?', [$classId, $cutoff, $cutoff]) as $op) {
                $predictorRegistry[] = 'task:'.$classId.':'.$op['id'];
            }
            if ((int) $query('SELECT COUNT(*) AS n FROM quiz_attempts WHERE quiz_id=? AND is_simulated=0 AND created_at<=?', [$spec['quiz'], $cutoff])[0]['n']) {
                throw new RuntimeException('Stop: posttest/predictor temporal overlap.');
            }
            $questionMaximum = (float) $query('SELECT SUM(points) AS n FROM questions WHERE quiz_id=?', [$spec['quiz']])[0]['n'];
            if ($questionMaximum !== 20.0) {
                throw new RuntimeException('Stop: assessment maximum changed.');
            }
            $details = $query('SELECT COUNT(*) AS n FROM quiz_attempts a LEFT JOIN
                (SELECT quiz_attempt_id,SUM(points_earned) AS earned,SUM(points_possible) AS possible FROM quiz_attempt_details GROUP BY quiz_attempt_id) d
                ON d.quiz_attempt_id=a.id WHERE a.quiz_id=? AND a.is_simulated=0 AND
                (d.quiz_attempt_id IS NULL OR ABS(d.earned-a.score)>0.00001 OR ABS(d.possible-a.total_points)>0.00001)', [$spec['quiz']]);
            if ((int) $details[0]['n']) {
                throw new RuntimeException('Stop: source attempt details do not reconcile with recorded scores.');
            }
            foreach ($roster as $person) {
                $uid = (int) $person['user_id'];
                $conflicts = (int) $query('SELECT COUNT(*) AS n FROM research_participants rp JOIN research_groups g ON g.id=rp.research_group_id WHERE rp.user_id=? AND g.research_experiment_id=1 AND g.id<>?', [$uid, $map['research_group_id']])[0]['n'];
                if ($conflicts) {
                    throw new RuntimeException('Stop: conflicting experiment assignment.');
                }
                $attempts = $query('SELECT id,score,total_points,created_at,updated_at FROM quiz_attempts WHERE user_id=? AND quiz_id=? AND is_simulated=0 ORDER BY id', [$uid, $spec['quiz']]);
                foreach ($attempts as $attempt) {
                    if ($attempt['score'] === null || (float) $attempt['total_points'] !== 20.0 || $attempt['score'] < 0 || $attempt['score'] > $attempt['total_points'] ||
                        $attempt['created_at'] <= $cutoff || $attempt['created_at'] < $map['published_at'] || $attempt['created_at'] > $map['expires_at'] ||
                        $attempt['updated_at'] < $attempt['created_at'] || $attempt['updated_at'] > $horizon) {
                        throw new RuntimeException('Stop: invalid outcome record requires exclusion/reconciliation, not a nonparticipation label.');
                    }
                }
                $attempt = $attempts ? end($attempts) : null; // latest valid non-simulated attempt; no retries currently present
                $group = $schema->groupingKey($uid);
                if (! isset($originalPairs[$group.':'.$classId])) {
                    throw new RuntimeException('Stop: grouping key does not match the preserved genuine cohort.');
                }
                $f = $features->build($uid, $classId, Carbon::parse($cutoff));
                $row = ['student_group_key' => $group, 'lab_session_id' => (string) $classId, 'class_code' => $spec['code'],
                    'feature_cutoff_at' => $cutoff, 'feature_version' => 'v5', 'eligible_session_count' => $f['eligible_session_count'],
                    'assigned_task_count' => $f['assigned_task_count'], ...$features->modelInput($f)];
                $featureRows[] = $row;
                $contexts[] = ['student_group_key' => $group, 'lab_session_id' => (string) $classId, 'feature_cutoff_at' => $cutoff, 'feature_version' => 'v5'];
                $evidenceRows[] = ['student_group_key' => $group, 'lab_session_id' => (string) $classId, 'opportunities' => [[
                    'opportunity_key' => 'quiz:'.$classId.':'.$spec['quiz'], 'type' => 'quiz', 'available_at' => $map['published_at'], 'closed_at' => $map['expires_at'],
                    'required' => true, 'eligible' => true, 'eligibility_verified' => true, 'collection_complete' => true,
                    'state' => $attempt ? 'completed' : 'not_completed', 'score' => $attempt ? (float) $attempt['score'] : null, 'max_score' => 20,
                    'event_at' => $attempt['created_at'] ?? null, 'recorded_at' => $attempt['updated_at'] ?? null,
                    'event_key' => $attempt ? 'quiz_attempt:'.$attempt['id'] : null, 'is_simulated' => false]]];
                $rows[] = [...$row, 'dataset_contract' => 'oop-temporal-v1', 'target_policy_version' => ResearchOutcomePolicyService::VERSION,
                    'outcome_quiz_id' => $spec['quiz'], 'outcome_completion_state' => $attempt ? 'completed' : 'not_completed',
                    'outcome_score' => $attempt ? (float) $attempt['score'] : null, 'outcome_max_score' => 20,
                    'outcome_percentage' => $attempt ? 100 * (float) $attempt['score'] / 20 : null,
                    'outcome_available_at' => $map['published_at'], 'outcome_closed_at' => $map['expires_at'],
                    'outcome_event_at' => $attempt['created_at'] ?? null, 'outcome_recorded_at' => $attempt['updated_at'] ?? null,
                    'outcome_attempt_key' => $attempt ? 'quiz_attempt:'.$attempt['id'] : null,
                    'risk_reason' => null, 'data_origin' => 'real', 'outcome_origin' => 'research_observed',
                    'experiment_group' => $spec['group'], 'experiment_id' => 1, 'experiment_assigned_at' => $person['assigned_at'],
                    'consented_at' => $person['consented_at'], 'posttest_mapped_at' => $map['mapped_at'],
                    'eligibility_basis' => 'mapped_consented_posttest_roster', 'required_opportunity_verified' => 'true',
                    'exemption_status' => 'none_documented', 'academic_risk_status' => null];
            }
        }
        $featureStream = fopen('php://temp', 'w+');
        fputcsv($featureStream, array_keys($featureRows[0]), ',', '"', '');
        foreach ($featureRows as $featureRow) {
            fputcsv($featureStream, array_values($featureRow), ',', '"', '');
        }
        rewind($featureStream);
        $featureCsv = stream_get_contents($featureStream);
        fclose($featureStream);
        $featureHash = hash('sha256', $featureCsv);
        $bundle = ['policy_version' => ResearchOutcomePolicyService::VERSION, 'feature_sha256' => $featureHash, 'prediction_cutoff' => $cutoff,
            'outcome_start' => $policy['outcome_start'], 'outcome_end' => $policy['outcome_end'], 'collected_at' => $horizon,
            'data_origin' => 'real', 'outcome_origin' => 'research_observed', 'approval_reference' => $policy['approval_reference'],
            'eligibility_verified' => true, 'predictor_opportunity_keys' => $predictorRegistry, 'rows' => $evidenceRows, 'simulation' => null];
        foreach ($rows as $index => &$row) {
            $single = $bundle;
            $single['rows'] = [$evidenceRows[$index]];
            $result = $evaluator->inspect([$contexts[$index]], $single, $policy);
            $row['risk_reason'] = $result['no_participation_at_risk_rows'] ? 'nonparticipation' : ($result['performance_at_risk_rows'] ? 'below_threshold' : 'meets_threshold');
            $row['academic_risk_status'] = $result['label_balance']['at_risk'] === 1 ? 'at_risk' : 'not_at_risk';
            $reasons[$row['risk_reason']]++;
        }
        unset($row);
        $summary = $evaluator->inspect($contexts, $bundle, $policy);
        if (count($rows) !== 108 || $reasons !== ['nonparticipation' => 36, 'below_threshold' => 1, 'meets_threshold' => 71]) {
            throw new RuntimeException('Stop: recomputed label counts materially differ from the reviewed cohort. Counts were not forced.');
        }
        foreach ($specs as $id => $spec) {
            $selected = array_filter($rows, fn ($row) => (int) $row['lab_session_id'] === $id);
            $completed = count(array_filter($selected, fn ($row) => $row['outcome_completion_state'] === 'completed'));
            if (count($selected) !== $spec['roster'] || $completed !== $spec['completed']) {
                throw new RuntimeException('Stop: class completion counts differ from the approved cohort.');
            }
            $classes[] = ['class_code' => $spec['code'], 'rows' => count($selected), 'completed' => $completed,
                'nonparticipation' => count($selected) - $completed,
                'at_risk' => count(array_filter($selected, fn ($row) => $row['academic_risk_status'] === 'at_risk')),
                'not_at_risk' => count(array_filter($selected, fn ($row) => $row['academic_risk_status'] === 'not_at_risk'))];
        }
    } finally {
        $pdo->exec('ROLLBACK');
    }
    if (isset($options['check-only'])) {
        echo json_encode(['check_only' => true, 'rows' => count($rows), 'reasons' => $reasons, 'classes' => $classes], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
        exit(0);
    }
    $header = array_keys($rows[0]);
    $featureOutput = fopen($stem.'.features.csv', 'x');
    if (! $featureOutput) {
        throw new RuntimeException('Cannot create new reconstructed feature companion.');
    }
    $created[] = $stem.'.features.csv';
    try {
        if (fwrite($featureOutput, $featureCsv) !== strlen($featureCsv) || ! fflush($featureOutput)) {
            throw new RuntimeException('Cannot flush reconstructed features.');
        }
    } finally {
        fclose($featureOutput);
    }
    $stream = fopen($output, 'x');
    if (! $stream) {
        throw new RuntimeException('Cannot create a new temporal dataset.');
    }
    $created[] = $output;
    try {
        if (fputcsv($stream, $header, ',', '"', '') === false) {
            throw new RuntimeException('Cannot write temporal dataset header.');
        }
        foreach ($rows as $row) {
            if (fputcsv($stream, array_values($row), ',', '"', '') === false) {
                throw new RuntimeException('Cannot write temporal dataset.');
            }
        }
        if (! fflush($stream)) {
            throw new RuntimeException('Cannot flush temporal dataset.');
        }
    } finally {
        fclose($stream);
    }
    $metadata = array_values(array_diff($header, [...$schema->core(), ...$schema->optional(), ...$schema->structured(), ...config('risk_features.metadata'), 'class_code', 'academic_risk_status']));
    $payload = ['dataset_contract' => 'oop-temporal-v1', 'target_policy_version' => ResearchOutcomePolicyService::VERSION,
        'dataset_sha256' => hash_file('sha256', $output), 'row_count' => count($rows), 'columns' => $header, 'reason_counts' => $reasons,
        'source_feature_filename' => basename($stem.'.features.csv'), 'feature_sha256' => $featureHash,
        'categories' => ['model_x_core' => $schema->core(), 'model_x_optional' => $schema->optional(),
            'structured_topic_encoding_pending' => $schema->structured(), 'target_y' => ['academic_risk_status'],
            'grouping_metadata' => array_values(array_intersect($header, [...config('risk_features.metadata'), ...config('risk_features.class_metadata')])),
            'outcome_metadata_never_x' => $metadata, 'excluded_predictors' => array_keys(config('risk_features.excluded'))],
        'policy' => [...$policy, 'prediction_cutoff' => $cutoff, 'nonparticipation_score' => 'NULL',
            'nonparticipation_label' => 'at_risk', 'eligibility_basis' => 'mapped_consented_posttest_roster'],
        'training_authorized' => false, 'generated_at' => $horizon];
    $provenance = ['artifact_kind' => 'genuine_research_temporal_cohort', 'official_institutional_grade' => false,
        'dataset_sha256' => $payload['dataset_sha256'], 'reconstructed_features_sha256' => $featureHash, 'late_feature_reference_sha256' => $lateHash,
        'predictors' => 'Reconstructed with unchanged StudentRiskFeatureService::build; no saved snapshots.',
        'classes' => $classes, 'aggregate_policy_check' => $summary, 'database' => 'local lmms_ccs_db_prod_1018',
        'database_mode' => 'SELECT only inside START TRANSACTION READ ONLY; ROLLBACK',
        'exemption_search' => ['withdrawal_or_decline' => 'Excluded by consented roster predicate', 'experiment_cancellation' => 'Active experiment verified',
            'dedicated_exemption_schema' => 'No dedicated columns/tables found; stop if any appear', 'exception_activity_matches' => $exceptionActivity,
            'attendance_exception_matches' => $attendanceExceptions, 'protocol_exception_mentions' => $protocolExceptions,
            'offline_records' => 'No additional exemption documents supplied; absence is limited to inspected application records.'],
        'retry_policy' => 'Latest valid non-simulated attempt by ID; invalid observed records block export rather than becoming nonparticipation.',
        'training_authorized' => false, 'source_sha256' => ['exporter' => hash_file('sha256', __FILE__),
            'features' => hash_file('sha256', app_path('Services/StudentRiskFeatureService.php')),
            'target_evaluator' => hash_file('sha256', app_path('Services/ResearchOutcomePolicyService.php'))]];
    foreach ([$stem.'.schema.json' => $payload, $stem.'.provenance.json' => $provenance, $stem.'.evidence.json' => $bundle] as $path => $data) {
        $stream = fopen($path, 'x');
        if (! $stream) {
            throw new RuntimeException('Cannot create new temporal companion file.');
        }
        $created[] = $path;
        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
            if (fwrite($stream, $json) !== strlen($json) || ! fflush($stream)) {
                throw new RuntimeException('Cannot flush temporal companion.');
            }
        } finally {
            fclose($stream);
        }
    }
    echo json_encode(['output' => $output, 'rows' => count($rows), 'reasons' => $reasons, 'classes' => $classes,
        'dataset_sha256' => $payload['dataset_sha256'], 'training_performed' => false], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $exception) {
    foreach ($created as $path) {
        if (is_file($path)) {
            unlink($path); // Only files exclusively created by this failed invocation.
        }
    }
    fwrite(STDERR, "Temporal export blocked; review cohort counts, exemptions, source integrity, temporal checks and output collisions. No database writes.\n");
    exit(2);
}
