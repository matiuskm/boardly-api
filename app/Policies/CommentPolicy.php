<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Support\WorkspaceAccess;

class CommentPolicy
{
    public function create(User $user, Comment $comment): bool
    {
        return WorkspaceAccess::isMember($user, $comment->issue->board->project->workspace);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return WorkspaceAccess::canManageWorkspace($user, $comment->issue->board->project->workspace);
    }
}
