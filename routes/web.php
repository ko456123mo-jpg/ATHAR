<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForensicsWorkspaceActionController;
use App\Http\Controllers\ForensicsWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/workspace/{section}', ForensicsWorkspaceController::class)
        ->whereIn('section', ['cases', 'evidence', 'jobs', 'tools', 'findings', 'reports', 'custody'])
        ->name('workspace');
    Route::post('/workspace/cases', [ForensicsWorkspaceActionController::class, 'storeCase'])->name('workspace.cases.store');
    Route::post('/workspace/evidence', [ForensicsWorkspaceActionController::class, 'storeEvidence'])->name('workspace.evidence.store');
    Route::post('/workspace/evidence/{evidence}/verify', [ForensicsWorkspaceActionController::class, 'verifyEvidence'])->name('workspace.evidence.verify');
    Route::post('/workspace/evidence/{evidence}/working-copy', [ForensicsWorkspaceActionController::class, 'createWorkingCopy'])->name('workspace.evidence.working-copy');
    Route::post('/workspace/jobs', [ForensicsWorkspaceActionController::class, 'storeJob'])->name('workspace.jobs.store');
    Route::post('/workspace/jobs/{job}/cancel', [ForensicsWorkspaceActionController::class, 'cancelJob'])->name('workspace.jobs.cancel');
    Route::post('/workspace/jobs/{job}/retry', [ForensicsWorkspaceActionController::class, 'retryJob'])->name('workspace.jobs.retry');
    Route::post('/workspace/findings', [ForensicsWorkspaceActionController::class, 'storeFinding'])->name('workspace.findings.store');
    Route::post('/workspace/findings/{finding}/review', [ForensicsWorkspaceActionController::class, 'reviewFinding'])->name('workspace.findings.review');
    Route::post('/workspace/reports', [ForensicsWorkspaceActionController::class, 'generateReport'])->name('workspace.reports.store');
    Route::post('/workspace/reports/{report}/approve', [ForensicsWorkspaceActionController::class, 'approveReport'])->name('workspace.reports.approve');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
