<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;
use App\Support\ProjectAccess;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Issue $issue): bool
    {
        return ProjectAccess::canViewProject($user, $issue->board->project);
    }

    public function create(User $user, Issue $issue): bool
    {
        return ProjectAccess::canManageIssues($user, $issue->board->project);
    }

    public function update(User $user, Issue $issue): bool
    {
        return ProjectAccess::canManageIssues($user, $issue->board->project);
    }

    public function delete(User $user, Issue $issue): bool
    {
        return ProjectAccess::canManageIssues($user, $issue->board->project);
    }

    public function move(User $user, Issue $issue): bool
    {
        return ProjectAccess::canManageIssues($user, $issue->board->project);
    }

    public function assign(User $user, Issue $issue): bool
    {
        return ProjectAccess::canManageProject($user, $issue->board->project);
    }
}
