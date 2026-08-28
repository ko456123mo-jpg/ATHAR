<?php

namespace App\Policies;

use App\Models\ForensicAgent;
use App\Models\User;

class ForensicAgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function manage(User $user, ForensicAgent $agent): bool
    {
        return $user->isAdministrator();
    }
}
