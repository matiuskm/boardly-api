<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\User;
use App\Support\WorkspaceAccess;

class BoardColumnPolicy
{
    public function viewAny(User $user, ?Board $board = null): bool
    {
        if (! $board) {
            return WorkspaceAccess::canManageAnyWorkspace($user);
        }

        return WorkspaceAccess::canManageWorkspace($user, $board->project->workspace);
    }

    public function create(User $user, ?Board $board = null): bool
    {
        if (! $board) {
            return WorkspaceAccess::canManageAnyWorkspace($user);
        }

        return WorkspaceAccess::canManageWorkspace($user, $board->project->workspace);
    }

    public function update(User $user, BoardColumn $column): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $column->board->project->workspace);
    }

    public function delete(User $user, BoardColumn $column): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $column->board->project->workspace);
    }
}
