<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Support\ProjectAccess;

class CommentPolicy
{
    public function create(User $user, Comment $comment): bool
    {
        return ProjectAccess::canManageIssues($user, $comment->issue->board->project);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return ProjectAccess::canManageProject($user, $comment->issue->board->project);
    }
}
