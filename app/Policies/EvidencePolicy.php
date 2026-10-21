<?php

namespace App\Policies;

use App\Models\InvestigationCase;

use App\Models\Evidence;
use App\Models\User;

class EvidencePolicy
{
    public function view(User $user, Evidence $evidence): bool
    {
        return $user->isAdministrator() || $evidence->investigationCase->members()->whereKey($user->getKey())->exists();
    }

    public function create(User $user): bool { return $user->hasRole('administrator', 'investigator'); }

    public function download(User $user, Evidence $evidence): bool
    {
        return $user->isAdministrator() || $evidence->investigationCase->members()->whereKey($user->getKey())->wherePivotIn('role_in_case', InvestigationCase::EDITABLE_ROLES)->exists();
    }
}
