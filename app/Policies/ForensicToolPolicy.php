<?php

namespace App\Policies;

use App\Models\ForensicTool;
use App\Models\User;

class ForensicToolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administrator', 'investigator', 'reviewer');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function manage(User $user, ForensicTool $tool): bool
    {
        return $user->isAdministrator();
    }
}
