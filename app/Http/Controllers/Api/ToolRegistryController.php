<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ToolVerificationService;
use App\Http\Controllers\Controller;
use App\Models\ForensicTool;
use App\Models\ToolCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ToolRegistryController extends Controller
{
    public function categories(): JsonResponse
    {
        $this->authorize('viewAny', ForensicTool::class);

        return response()->json(ToolCategory::query()->withCount('tools')->orderBy('name')->get());
    }

    public function storeCategory(Request $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('manage-tools');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:128', 'unique:tool_categories,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $category = ToolCategory::query()->create([
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($data['name']),
        ]);
        $audit->record($request->user(), 'tool_category.created', $category);

        return response()->json($category, 201);
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ForensicTool::class);

        return response()->json(ForensicTool::query()->with(['category', 'operations', 'versions.agent'])->orderBy('name')->get());
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', ForensicTool::class);
        $data = $request->validate([
            'category_id' => ['required', 'exists:tool_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'adapter_key' => ['required', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', 'max:128', 'unique:tools,adapter_key'],
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

    public function storeOperation(Request $request, ForensicTool $tool, AuditLogger $audit): JsonResponse
    {
        $this->authorize('manage', $tool);
        $data = $request->validate([
            'operation_key' => ['required', 'alpha_dash', 'max:128'],
            'evidence_types' => ['required', 'array', 'min:1'],
            'evidence_types.*' => ['in:disk,ram,pcap,pcapng,mobile,log,database,file'],
            'input_schema' => ['nullable', 'array'],
            'output_schema' => ['nullable', 'array'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
            'max_output_bytes' => ['required', 'integer', 'min:1024', 'max:104857600'],
            'enabled' => ['boolean'],
        ]);
        $operation = $tool->operations()->create([
            'operation_key' => $data['operation_key'],
            'evidence_types_json' => array_values(array_unique($data['evidence_types'])),
            'input_schema_json' => $data['input_schema'] ?? [],
            'output_schema_json' => $data['output_schema'] ?? [],
            'timeout_seconds' => $data['timeout_seconds'],
            'max_output_bytes' => $data['max_output_bytes'],
            'enabled' => $data['enabled'] ?? false,
        ]);
        $audit->record($request->user(), 'tool_operation.created', $operation, metadata: ['tool_id' => $tool->getKey(), 'adapter_key' => $tool->adapter_key]);

        return response()->json($operation, 201);
    }

    public function recordVerification(Request $request, ForensicTool $tool, ToolVerificationService $verification, AuditLogger $audit): JsonResponse
    {
        $this->authorize('manage', $tool);
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
