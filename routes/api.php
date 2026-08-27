<?php

use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\ForensicAgentController;
use App\Http\Controllers\Api\ForensicAgentManagementController;
use App\Http\Controllers\Api\AnalysisJobController;
use App\Http\Controllers\Api\ToolRegistryController;
use App\Http\Controllers\Api\FindingController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('v1/cases', [CaseController::class, 'index']);
    Route::post('v1/cases', [CaseController::class, 'store']);
    Route::get('v1/cases/{case}', [CaseController::class, 'show']);
    Route::post('v1/cases/{case}/close', [CaseController::class, 'close']);
    Route::post('v1/cases/{case}/evidence', [EvidenceController::class, 'store']);
    Route::post('v1/evidence/{evidence}/verify', [EvidenceController::class, 'verify']);
    Route::post('v1/evidence/{evidence}/working-copy', [EvidenceController::class, 'createWorkingCopy']);
    Route::get('v1/evidence/{evidence}/download', [EvidenceController::class, 'download']);
    Route::get('v1/evidence/{evidence}/custody', [EvidenceController::class, 'custody']);
    Route::post('v1/cases/{case}/analysis-jobs', [AnalysisJobController::class, 'store']);
    Route::get('v1/analysis-jobs/{job:public_id}', [AnalysisJobController::class, 'show']);
    Route::post('v1/analysis-jobs/{job:public_id}/cancel', [AnalysisJobController::class, 'cancel']);
    Route::post('v1/analysis-jobs/{job:public_id}/retry', [AnalysisJobController::class, 'retry']);
    Route::get('v1/tool-categories', [ToolRegistryController::class, 'categories']);
    Route::post('v1/tool-categories', [ToolRegistryController::class, 'storeCategory'])->can('manage-tools');
    Route::get('v1/tools', [ToolRegistryController::class, 'index']);
    Route::post('v1/tools', [ToolRegistryController::class, 'store'])->can('manage-tools');
    Route::post('v1/tools/{tool}/operations', [ToolRegistryController::class, 'storeOperation'])->can('manage-tools');
    Route::post('v1/tools/{tool}/verification', [ToolRegistryController::class, 'recordVerification'])->can('manage-tools');
    Route::post('v1/cases/{case}/findings', [FindingController::class, 'store']);
    Route::post('v1/findings/{finding}/review', [FindingController::class, 'review'])->can('review-findings');
    Route::post('v1/cases/{case}/reports', [ReportController::class, 'store']);
    Route::post('v1/reports/{report}/approve', [ReportController::class, 'approve'])->can('review-findings');
    Route::get('v1/reports/{report}', [ReportController::class, 'show']);
    Route::get('v1/agents', [ForensicAgentManagementController::class, 'index'])->can('manage-agents');
    Route::post('v1/agents', [ForensicAgentManagementController::class, 'store'])->can('manage-agents');
    Route::post('v1/agents/{agent}/rotate-token', [ForensicAgentManagementController::class, 'rotateToken'])->can('manage-agents');
    Route::post('v1/agents/{agent}/retire', [ForensicAgentManagementController::class, 'retire'])->can('manage-agents');
});

Route::prefix('v1/agent')->middleware('agent.token')->group(function (): void {
    Route::post('heartbeat', [ForensicAgentController::class, 'heartbeat']);
    Route::post('jobs/claim', [ForensicAgentController::class, 'claim']);
    Route::post('jobs/{job:public_id}/status', [ForensicAgentController::class, 'status']);
    Route::post('jobs/{job:public_id}/result', [ForensicAgentController::class, 'result']);
});
