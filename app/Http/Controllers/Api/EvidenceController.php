<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\EvidenceService;
use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Models\InvestigationCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvidenceController extends Controller
{
    public function store(Request $request, InvestigationCase $case, EvidenceService $evidenceService): JsonResponse
    {
        $this->authorize('update', $case);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:pcap,pcapng,disk_image,memory_image,file_archive,document,other'],
            'source' => ['nullable', 'string', 'max:255'],
            'evidence' => ['required', 'file', 'max:'.config('forensics.max_evidence_upload_kilobytes')],
        ]);

        $evidence = $evidenceService->register($case, $request->user(), $data['evidence'], $data);

        return response()->json($evidence, 201);
    }

    public function verify(Request $request, Evidence $evidence, EvidenceService $evidenceService): JsonResponse
    {
        $this->authorize('view', $evidence);
        $valid = $evidenceService->verify($evidence, $request->user());

        return response()->json(['evidence_id' => $evidence->getKey(), 'integrity_valid' => $valid, 'status' => $evidence->fresh()->status]);
    }

    public function custody(Evidence $evidence): JsonResponse
    {
        $this->authorize('view', $evidence);

        return response()->json($evidence->custodyEvents()->with('actor:id,name,email')->oldest('id')->get());
    }
}
