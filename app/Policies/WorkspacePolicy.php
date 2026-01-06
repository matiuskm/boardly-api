<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->users()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->users()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->users()
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();
    }
}
