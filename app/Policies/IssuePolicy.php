<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;
use App\Support\WorkspaceAccess;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageAnyWorkspace($user);
    }

    public function view(User $user, Issue $issue): bool
    {
        return WorkspaceAccess::isMember($user, $issue->board->project->workspace);
    }

    public function create(User $user, Issue $issue): bool
    {
        return WorkspaceAccess::isMember($user, $issue->board->project->workspace);
    }

    public function update(User $user, Issue $issue): bool
    {
        return WorkspaceAccess::isMember($user, $issue->board->project->workspace);
    }

    public function delete(User $user, Issue $issue): bool
    {
        return WorkspaceAccess::isMember($user, $issue->board->project->workspace);
    }

    public function move(User $user, Issue $issue): bool
    {
        return WorkspaceAccess::isMember($user, $issue->board->project->workspace);
    }

    public function assign(User $user, Issue $issue): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $issue->board->project->workspace);
    }
}
