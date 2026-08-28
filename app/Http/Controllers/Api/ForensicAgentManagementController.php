<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\ForensicAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForensicAgentManagementController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ForensicAgent::class);

        return response()->json(ForensicAgent::query()->withCount('jobs')->latest()->paginate(20));
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', ForensicAgent::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:agents,name'],
            'hostname' => ['nullable', 'string', 'max:255'],
        ]);
        $bootstrapToken = Str::random(64);
        $agent = ForensicAgent::query()->create([
            ...$data,
            'token_hash' => Hash::make($bootstrapToken),
            'status' => 'offline',
        ]);
        $audit->record($request->user(), 'agent.registered', $agent, metadata: ['hostname' => $agent->hostname]);

        return response()->json(['agent' => $agent, 'bootstrap_token' => $bootstrapToken], 201);
    }

    public function rotateToken(Request $request, ForensicAgent $agent, AuditLogger $audit): JsonResponse
    {
        $this->authorize('manage', $agent);
        abort_if($agent->status === 'retired', 422, 'Retired agents cannot receive a new token.');
        $bootstrapToken = Str::random(64);
        $agent->update(['token_hash' => Hash::make($bootstrapToken), 'status' => 'offline']);
        $audit->record($request->user(), 'agent.token_rotated', $agent);

        return response()->json(['agent' => $agent->fresh(), 'bootstrap_token' => $bootstrapToken]);
    }

    public function retire(Request $request, ForensicAgent $agent, AuditLogger $audit): JsonResponse
    {
        $this->authorize('manage', $agent);
        abort_if($agent->status === 'retired', 422, 'Agent is already retired.');
        $agent->update(['status' => 'retired']);
        $audit->record($request->user(), 'agent.retired', $agent);

        return response()->json($agent->fresh());
    }
}
