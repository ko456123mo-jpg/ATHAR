<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\InvestigationCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InvestigationCase::class);
        $user = $request->user();
        $query = InvestigationCase::query()->with('creator:id,name,email')->latest();
        if (! $user->isAdministrator()) {
            $query->whereHas('members', fn ($members) => $members->whereKey($user->getKey()));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', InvestigationCase::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['required', 'in:low,medium,high,critical'],
        ]);
        $user = $request->user();

        $case = DB::transaction(function () use ($data, $user, $audit): InvestigationCase {
            $case = InvestigationCase::query()->create([
                ...$data,
                'case_number' => 'ATH-'.strtoupper((string) Str::ulid()),
                'created_by' => $user->getKey(),
            ]);
            $case->members()->attach($user->getKey(), ['role_in_case' => 'lead']);
            $audit->record($user, 'case.created', $case);

            return $case;
        });

        return response()->json($case->load('members:id,name,email'), 201);
    }

    public function show(InvestigationCase $case): JsonResponse
    {
        $this->authorize('view', $case);

        return response()->json($case->load(['members:id,name,email', 'evidence.hashes', 'analysisJobs']));
    }

    public function close(Request $request, InvestigationCase $case, AuditLogger $audit): JsonResponse
    {
        $this->authorize('close', $case);
        $case->update(['status' => 'closed', 'closed_at' => now()]);
        $audit->record($request->user(), 'case.closed', $case);

        return response()->json($case->fresh());
    }
}
