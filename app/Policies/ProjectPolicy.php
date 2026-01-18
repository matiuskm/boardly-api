<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\ProjectAccess;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return ProjectAccess::canViewProject($user, $project);
    }

    public function create(User $user, Project $project): bool
    {
        return ProjectAccess::canManageProject($user, $project);
    }

    public function update(User $user, Project $project): bool
    {
        return ProjectAccess::canManageProject($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return ProjectAccess::canManageProject($user, $project);
    }
}
