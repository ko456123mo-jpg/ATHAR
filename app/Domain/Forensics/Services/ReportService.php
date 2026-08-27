<?php

namespace App\Domain\Forensics\Services;

use App\Models\ForensicReport;
use App\Models\InvestigationCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    public function __construct(private readonly AuditLogger $audit, private readonly ChainOfCustodyService $custody)
    {
    }

    public function generate(InvestigationCase $case, User $actor): ForensicReport
    {
        return DB::transaction(function () use ($case, $actor): ForensicReport {
            $version = (int) $case->reports()->max('version') + 1;
            $findings = $case->findings()->with(['evidence', 'analysisResult.job.tool'])->whereIn('status', ['reviewed', 'confirmed'])->get();
            $content = $this->buildContent($case, $findings->all(), $version);
            $key = "reports/{$case->getKey()}/v{$version}/report.md";
            Storage::disk(config('forensics.evidence_disk'))->put($key, $content);
            $report = ForensicReport::query()->create([
                'case_id' => $case->getKey(),
                'version' => $version,
                'status' => 'draft',
                'generated_by' => $actor->getKey(),
                'file_key' => $key,
                'content_hash' => hash('sha256', $content),
            ]);
            $case->evidence()->each(fn ($evidence) => $this->custody->record($evidence, $actor, 'report_generated', 'report.generated', ['report_id' => $report->getKey(), 'report_version' => $version]));
            $this->audit->record($actor, 'report.generated', $report, metadata: ['case_id' => $case->getKey(), 'version' => $version]);

            return $report;
        });
    }

    private function buildContent(InvestigationCase $case, array $findings, int $version): string
    {
        $lines = [
            '# ATHAR Investigation Report',
            '',
            "- Case: {$case->case_number} — {$case->name}",
            "- Report version: {$version}",
            '- Generated at: '.now()->utc()->toIso8601String(),
            '- Status: Draft — requires investigator and reviewer approval.',
            '',
            '## Reviewed Findings',
            '',
        ];
        if ($findings === []) {
            $lines[] = 'No reviewed or confirmed findings were available when this report was generated.';
        }
        foreach ($findings as $finding) {
            $lines[] = "### {$finding->title}";
            $lines[] = "- Severity: {$finding->severity}";
            $lines[] = "- Evidence: ".($finding->evidence?->evidence_number ?? 'Not linked');
            $lines[] = "- Analysis source: ".($finding->analysisResult?->job?->tool?->name ?? 'Manual investigator finding');
            $lines[] = '';
            $lines[] = $finding->description;
            $lines[] = '';
        }
        $lines[] = '## Evidence Integrity Notice';
        $lines[] = 'All conclusions require investigator review. The original evidence is stored privately and is not modified by report generation.';

        return implode("\n", $lines)."\n";
    }
}
