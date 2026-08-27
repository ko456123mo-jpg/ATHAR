<?php

use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\EvidenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('v1/cases', [CaseController::class, 'index']);
    Route::post('v1/cases', [CaseController::class, 'store']);
    Route::get('v1/cases/{case}', [CaseController::class, 'show']);
    Route::post('v1/cases/{case}/close', [CaseController::class, 'close']);
    Route::post('v1/cases/{case}/evidence', [EvidenceController::class, 'store']);
    Route::post('v1/evidence/{evidence}/verify', [EvidenceController::class, 'verify']);
    Route::get('v1/evidence/{evidence}/custody', [EvidenceController::class, 'custody']);
});
