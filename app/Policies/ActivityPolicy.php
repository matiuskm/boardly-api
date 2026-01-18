<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use App\Models\Workspace;
use App\Support\WorkspaceAccess;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageAnyWorkspace($user);
    }

    public function view(User $user, Activity|Workspace $resource): bool
    {
        if ($resource instanceof Workspace) {
            return WorkspaceAccess::isMember($user, $resource);
        }

        return WorkspaceAccess::canManageWorkspace($user, $resource->workspace);
    }
}
