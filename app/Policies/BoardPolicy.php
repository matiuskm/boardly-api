<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Support\WorkspaceAccess;

class BoardPolicy
{
    public function view(User $user, Board $board): bool
    {
        return WorkspaceAccess::isMember($user, $board->project->workspace);
    }

    public function create(User $user, Board $board): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $board->project->workspace);
    }

    public function update(User $user, Board $board): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $board->project->workspace);
    }

    public function delete(User $user, Board $board): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $board->project->workspace);
    }
}
