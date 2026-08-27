<?php

namespace Tests\Unit;

use App\Domain\Forensics\Services\EvidenceHashService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EvidenceHashServiceTest extends TestCase
{
    public function test_it_calculates_and_verifies_a_sha256_hash(): void
    {
        $path = storage_path('framework/testing/athar-benign-fixture.txt');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'ATHAR benign evidence fixture');

        $service = app(EvidenceHashService::class);
        $hash = $service->calculateSha256($path);

        $this->assertSame(hash('sha256', 'ATHAR benign evidence fixture'), $hash);
        $this->assertTrue($service->matches($path, $hash));
        $this->assertFalse($service->matches($path, str_repeat('0', 64)));
    }
}
