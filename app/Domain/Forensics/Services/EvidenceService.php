<?php

namespace App\Domain\Forensics\Services;

use App\Models\Evidence;
use App\Models\EvidenceHash;
use App\Models\InvestigationCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenceService
{
    public function __construct(
        private readonly EvidenceHashService $hashes,
        private readonly ChainOfCustodyService $custody,
        private readonly AuditLogger $audit,
    ) {
    }

    public function register(InvestigationCase $case, User $actor, UploadedFile $file, array $metadata): Evidence
    {
        $disk = config('forensics.evidence_disk');
        $evidenceNumber = 'EVD-'.strtoupper((string) Str::ulid());
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $directory = "evidence/{$case->getKey()}/{$evidenceNumber}";
        $storageKey = "{$directory}/original.{$extension}";

        Storage::disk($disk)->putFileAs($directory, $file, "original.{$extension}");

        try {
            return DB::transaction(function () use ($case, $actor, $file, $metadata, $disk, $evidenceNumber, $storageKey): Evidence {
                $evidence = Evidence::query()->create([
                    'case_id' => $case->getKey(),
                    'evidence_number' => $evidenceNumber,
                    'name' => $metadata['name'],
                    'original_filename' => $file->getClientOriginalName(),
                    'type' => $metadata['type'],
                    'storage_disk' => $disk,
                    'storage_key' => $storageKey,
                    'size_bytes' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'source' => $metadata['source'] ?? null,
                    'status' => 'registered',
                    'uploaded_by' => $actor->getKey(),
                ]);

                $absolutePath = Storage::disk($disk)->path($storageKey);
                $hash = $this->hashes->calculateSha256($absolutePath);
                EvidenceHash::query()->create([
                    'evidence_id' => $evidence->getKey(),
                    'algorithm' => 'sha256',
                    'value' => $hash,
                    'calculated_at' => now(),
                    'calculated_by' => $actor->getKey(),
                ]);

                $this->custody->record($evidence, $actor, 'registered', 'evidence.registered', [
                    'storage_key' => $storageKey,
                    'sha256' => $hash,
                    'size_bytes' => $file->getSize(),
                ]);
                $this->audit->record($actor, 'evidence.registered', $evidence, metadata: ['case_id' => $case->getKey()]);

                return $evidence->fresh(['hashes', 'custodyEvents']);
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($storageKey);
            throw $exception;
        }
    }

    public function verify(Evidence $evidence, ?User $actor): bool
    {
        $expected = $evidence->hashes()->where('algorithm', 'sha256')->latest('id')->value('value');
        $path = Storage::disk($evidence->storage_disk)->path($evidence->storage_key);
        $valid = is_string($expected) && $this->hashes->matches($path, $expected);

        $evidence->update(['status' => $valid ? 'verified' : 'integrity_failed']);
        $this->custody->record($evidence, $actor, 'verified', $valid ? 'evidence.sha256_verified' : 'evidence.integrity_failed', ['valid' => $valid]);
        $this->audit->record($actor, $valid ? 'evidence.verified' : 'evidence.integrity_failed', $evidence);

        return $valid;
    }

    public function createWorkingCopy(Evidence $evidence, User $actor): Evidence
    {
        $disk = Storage::disk($evidence->storage_disk);
        $extension = pathinfo($evidence->storage_key, PATHINFO_EXTENSION) ?: 'bin';
        $key = "evidence/{$evidence->case_id}/{$evidence->evidence_number}/working/".Str::ulid()."/input.{$extension}";

        if (! $disk->copy($evidence->storage_key, $key)) {
            throw new \RuntimeException('Unable to create controlled evidence working copy.');
        }

        $hash = $this->hashes->calculateSha256($disk->path($key));
        $evidence->update([
            'working_copy_key' => $key,
            'working_copy_hash' => $hash,
            'working_copy_created_at' => now(),
        ]);
        $this->custody->record($evidence, $actor, 'working_copy_created', 'evidence.working_copy_created', ['working_copy_key' => $key, 'sha256' => $hash]);
        $this->audit->record($actor, 'evidence.working_copy_created', $evidence);

        return $evidence->fresh();
    }

    public function recordDownload(Evidence $evidence, User $actor): void
    {
        $this->custody->record($evidence, $actor, 'accessed', 'evidence.download_requested');
        $this->audit->record($actor, 'evidence.download_requested', $evidence);
    }
}
