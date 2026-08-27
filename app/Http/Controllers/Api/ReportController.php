<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ChainOfCustodyService;
use App\Domain\Forensics\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Models\ForensicReport;
use App\Models\InvestigationCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function store(Request $request, InvestigationCase $case, ReportService $reports): JsonResponse
    {
        $this->authorize('update', $case);

        return response()->json($reports->generate($case, $request->user()), 201);
    }

    public function approve(Request $request, ForensicReport $report, AuditLogger $audit, ChainOfCustodyService $custody): JsonResponse
    {
        $this->authorize('view', $report->investigationCase);
        Gate::authorize('review-findings');
        $report->update(['status' => 'approved', 'approved_by' => $request->user()->getKey(), 'approved_at' => now()]);
        $report->investigationCase->evidence()->each(fn ($evidence) => $custody->record($evidence, $request->user(), 'report_approved', 'report.approved', ['report_id' => $report->getKey()]));
        $audit->record($request->user(), 'report.approved', $report);

        return response()->json($report->fresh());
    }

    public function show(ForensicReport $report): JsonResponse
    {
        $this->authorize('view', $report->investigationCase);

        return response()->json($report);
    }
}
