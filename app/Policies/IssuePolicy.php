<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;
use App\Support\WorkspaceAccess;

class IssuePolicy
{
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
}
