<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Support\WorkspaceAccess;

class ActivityPolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return WorkspaceAccess::isMember($user, $workspace);
    }
}
