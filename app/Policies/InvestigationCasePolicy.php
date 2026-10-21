<?php

namespace App\Policies;

use App\Models\InvestigationCase;
use App\Models\User;

class InvestigationCasePolicy
{
    public function viewAny(User $user): bool { return $user->hasRole('administrator', 'investigator', 'reviewer'); }
    public function view(User $user, InvestigationCase $case): bool { return $user->isAdministrator() || $case->members()->whereKey($user->getKey())->exists(); }
    public function create(User $user): bool { return $user->hasRole('administrator', 'investigator'); }
    public function update(User $user, InvestigationCase $case): bool { return $user->isAdministrator() || $case->members()->whereKey($user->getKey())->wherePivotIn('role_in_case', InvestigationCase::EDITABLE_ROLES)->exists(); }
    public function close(User $user, InvestigationCase $case): bool { return $this->update($user, $case); }
}
