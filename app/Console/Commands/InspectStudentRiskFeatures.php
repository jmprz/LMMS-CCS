<?php

namespace App\Console\Commands;

use App\Services\StudentRiskFeatureService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class InspectStudentRiskFeatures extends Command
{
    protected $signature = 'risk:features
                            {user_id : Student user ID}
                            {lab_session_id : Lab session ID}
                            {--cutoff= : Optional cutoff, e.g. 2026-09-30 17:00:00}
                            {--save : Persist the generated snapshot}';

    protected $description = 'Inspect or save ML risk features for one student and class';

    public function handle(StudentRiskFeatureService $service): int
    {
        try {
            $cutoff = $this->option('cutoff')
                ? Carbon::parse($this->option('cutoff'))
                : now();

            if ($this->option('save')) {
                $snapshot = $service->snapshot(
                    (int) $this->argument('user_id'),
                    (int) $this->argument('lab_session_id'),
                    $cutoff
                );

                $features = $snapshot->toArray();
                $this->info("Saved student_feature_snapshots #{$snapshot->id}");
            } else {
                $features = $service->build(
                    (int) $this->argument('user_id'),
                    (int) $this->argument('lab_session_id'),
                    $cutoff
                );
            }

            $this->newLine();

            $this->table(
                ['Feature', 'Value'],
                collect($features)
                    ->except(['additional_features'])
                    ->map(fn ($value, $key) => [
                        $key,
                        $value instanceof \DateTimeInterface
                            ? $value->format('Y-m-d H:i:s')
                            : ($value ?? 'NULL'),
                    ])
                    ->values()
                    ->all()
            );

            $this->newLine();
            $this->line('Additional features:');
            $this->line(json_encode(
                $features['additional_features'] ?? [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ));

            $this->newLine();
            $this->line('ML '.StudentRiskFeatureService::FEATURE_VERSION.' input:');
            $this->line(json_encode(
                $service->modelInput($features),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
