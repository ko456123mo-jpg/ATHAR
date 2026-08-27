<?php

namespace App\Domain\Forensics\Services;

use RuntimeException;

class EvidenceHashService
{
    public function calculateSha256(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Evidence file is not readable.');
        }

        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new RuntimeException('SHA-256 calculation failed.');
        }

        return $hash;
    }

    public function matches(string $path, string $expectedHash): bool
    {
        return hash_equals(strtolower($expectedHash), strtolower($this->calculateSha256($path)));
    }
}
