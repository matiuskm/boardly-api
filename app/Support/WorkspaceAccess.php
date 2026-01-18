<?php

namespace App\Support;

use App\Models\User;
use App\Models\Workspace;

class WorkspaceAccess
{
    public static function isMember(User $user, Workspace $workspace): bool
    {
        if ($workspace->relationLoaded('members')) {
            return $workspace->members->contains('id', $user->id);
        }

        return $workspace->members()->where('user_id', $user->id)->exists();
    }

    public static function role(User $user, Workspace $workspace): ?string
    {
        if ($workspace->relationLoaded('members')) {
            $member = $workspace->members->firstWhere('id', $user->id);
            return $member?->pivot?->role;
        }

        return $workspace->members()
            ->where('user_id', $user->id)
            ->value('role');
    }

    public static function canManageWorkspace(User $user, Workspace $workspace): bool
    {
        $role = self::role($user, $workspace);

        return in_array($role, ['owner', 'admin'], true);
    }

    /**
     * @return array<int, string>
     */
    public static function managedWorkspaceIds(User $user): array
    {
        return $user->workspaces()
            ->wherePivotIn('role', ['owner', 'admin'])
            ->pluck('workspaces.id')
            ->all();
    }

    public static function canManageAnyWorkspace(User $user): bool
    {
        return ! empty(self::managedWorkspaceIds($user));
    }
}
