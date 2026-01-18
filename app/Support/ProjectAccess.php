<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;

class ProjectAccess
{
    public static function role(User $user, Project $project): ?string
    {
        if ($project->relationLoaded('members')) {
            $member = $project->members->firstWhere('id', $user->id);
            return $member?->pivot?->role;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->value('role');
    }

    public static function canManageProject(User $user, Project $project): bool
    {
        $role = self::role($user, $project);

        if (in_array($role, ['manager'], true)) {
            return true;
        }

        return WorkspaceAccess::canManageWorkspace($user, $project->workspace);
    }

    public static function canManageIssues(User $user, Project $project): bool
    {
        $role = self::role($user, $project);

        if (in_array($role, ['manager', 'contributor'], true)) {
            return true;
        }

        return WorkspaceAccess::isMember($user, $project->workspace);
    }

    public static function canViewProject(User $user, Project $project): bool
    {
        $role = self::role($user, $project);

        if ($role !== null) {
            return true;
        }

        return WorkspaceAccess::isMember($user, $project->workspace);
    }
}
