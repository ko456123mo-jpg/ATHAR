<?php

namespace App\Console\Commands;

use App\Models\ForensicAgent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateForensicAgentToken extends Command
{
    protected $signature = 'athar:agent-token {name : Unique CAINE agent name} {--hostname= : Optional CAINE hostname}';
    protected $description = 'Create or rotate a one-time Forensic Agent token. Store the displayed token only in CAINE.';

    public function handle(): int
    {
        $token = Str::random(64);
        $agent = ForensicAgent::query()->updateOrCreate(
            ['name' => $this->argument('name')],
            ['token_hash' => Hash::make($token), 'hostname' => $this->option('hostname'), 'status' => 'offline'],
        );
        $this->warn('Copy this token now; it will not be shown again.');
        $this->line($token);
        $this->info("Agent {$agent->name} is ready for CAINE configuration.");

        return self::SUCCESS;
    }
}
