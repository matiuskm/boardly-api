<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Support\ProjectAccess;

class BoardPolicy
{
    public function view(User $user, Board $board): bool
    {
        return ProjectAccess::canViewProject($user, $board->project);
    }

    public function create(User $user, Board $board): bool
    {
        return ProjectAccess::canManageProject($user, $board->project);
    }

    public function update(User $user, Board $board): bool
    {
        return ProjectAccess::canManageProject($user, $board->project);
    }

    public function delete(User $user, Board $board): bool
    {
        return ProjectAccess::canManageProject($user, $board->project);
    }
}
