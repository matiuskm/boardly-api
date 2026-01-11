<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\WorkspaceAccess;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return WorkspaceAccess::isMember($user, $project->workspace);
    }

    public function create(User $user, Project $project): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $project->workspace);
    }

    public function update(User $user, Project $project): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $project->workspace);
    }

    public function delete(User $user, Project $project): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $project->workspace);
    }
}
