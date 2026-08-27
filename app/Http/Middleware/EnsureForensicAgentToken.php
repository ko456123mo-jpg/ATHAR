<?php

namespace App\Http\Middleware;

use App\Models\ForensicAgent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureForensicAgentToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! is_string($token) || $token === '') {
            abort(401, 'Agent token is required.');
        }

        $agent = ForensicAgent::query()->whereIn('status', ['online', 'offline'])->get()
            ->first(fn (ForensicAgent $candidate): bool => Hash::check($token, $candidate->token_hash));
        if (! $agent) {
            abort(401, 'Agent token is invalid.');
        }

        $request->attributes->set('forensicAgent', $agent);

        return $next($request);
    }
}
