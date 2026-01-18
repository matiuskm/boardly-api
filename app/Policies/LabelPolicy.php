<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use App\Support\WorkspaceAccess;

class LabelPolicy
{
    public function viewAny(User $user, ?Workspace $workspace = null): bool
    {
        if (! $workspace) {
            return WorkspaceAccess::canManageAnyWorkspace($user);
        }

        return WorkspaceAccess::isMember($user, $workspace);
    }

    public function create(User $user, ?Workspace $workspace = null): bool
    {
        if (! $workspace) {
            return WorkspaceAccess::canManageAnyWorkspace($user);
        }

        return WorkspaceAccess::canManageWorkspace($user, $workspace);
    }

    public function update(User $user, Label $label): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $label->workspace);
    }

    public function delete(User $user, Label $label): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $label->workspace);
    }
}
