<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Support\WorkspaceAccess;

class WorkspacePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return WorkspaceAccess::isMember($user, $workspace);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return WorkspaceAccess::role($user, $workspace) === 'owner';
    }
}
