<?php

namespace App\Console\Commands;

use App\Services\ResearchOutcomeFileService;
use Illuminate\Console\Command;

class ReportResearchOutcomeReadiness extends Command
{
    protected $signature = 'risk:research-outcome-readiness {feature-csv} {evidence-json}';

    protected $description = 'Read-only aggregate readiness for the separately versioned later-academic research target';

    public function handle(ResearchOutcomeFileService $service): int
    {
        try {
            $report = $service->inspect($this->argument('feature-csv'), $this->argument('evidence-json'));
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return $report['ready_for_review'] ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable) {
            // Never echo raw evidence cells, group keys, free-text references or filenames.
            $this->error('Research outcome readiness blocked: invalid configuration, evidence contract, provenance or temporal/leakage checks. No labels exported.');

            return self::FAILURE;
        }
    }
}
