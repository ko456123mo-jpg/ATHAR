<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ToolVerificationService;
use App\Http\Controllers\Controller;
use App\Models\ForensicTool;
use App\Models\ToolCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ToolRegistryController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(ToolCategory::query()->withCount('tools')->orderBy('name')->get());
    }

    public function index(): JsonResponse
    {
        return response()->json(ForensicTool::query()->with(['category', 'operations', 'versions.agent'])->orderBy('name')->get());
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:tool_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'adapter_key' => ['required', 'alpha_dash', 'max:128', 'unique:tools,adapter_key'],
        ]);
        $tool = ForensicTool::query()->create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'verification_state' => 'cataloged',
            'enabled' => false,
        ]);
        $audit->record($request->user(), 'tool.cataloged', $tool);

        return response()->json($tool, 201);
    }

    public function recordVerification(Request $request, ForensicTool $tool, ToolVerificationService $verification, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'verification_state' => ['required', 'in:configured,installed,executable,end_to_end_verified'],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'version' => ['nullable', 'string', 'max:128'],
            'path' => ['nullable', 'string', 'max:512'],
        ]);
        $version = $verification->record($tool, $data['verification_state'], $data);
        $audit->record($request->user(), 'tool.verification_recorded', $tool, metadata: ['state' => $data['verification_state'], 'tool_version_id' => $version->getKey()]);

        return response()->json($tool->fresh(['versions.agent']));
    }
}
