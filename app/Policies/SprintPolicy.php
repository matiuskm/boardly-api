<?php

namespace App\Policies;

use App\Models\Sprint;
use App\Models\User;
use App\Support\ProjectAccess;

class SprintPolicy
{
    public function view(User $user, Sprint $sprint): bool
    {
        return ProjectAccess::canViewProject($user, $sprint->project);
    }

    public function create(User $user, Sprint $sprint): bool
    {
        return ProjectAccess::canManageProject($user, $sprint->project);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return ProjectAccess::canManageProject($user, $sprint->project);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return ProjectAccess::canManageProject($user, $sprint->project);
    }
}
