<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;

class ActivityLogger
{
    public function issueCreated(User $actor, Issue $issue): Activity
    {
        return $this->log($actor, $issue, 'issue.created');
    }

    public function issueMoved(User $actor, Issue $issue, array $meta): Activity
    {
        return $this->log($actor, $issue, 'issue.moved', $meta);
    }

    public function issueAssigned(User $actor, Issue $issue, ?string $fromUserId, ?string $toUserId): Activity
    {
        return $this->log($actor, $issue, 'issue.assigned', [
            'from' => $fromUserId,
            'to' => $toUserId,
        ]);
    }

    public function issueDeleted(User $actor, Issue $issue): Activity
    {
        return $this->log($actor, $issue, 'issue.deleted');
    }

    public function issueCommented(User $actor, Comment $comment): Activity
    {
        return $this->log($actor, $comment->issue, 'issue.commented', [
            'comment_id' => $comment->id,
        ]);
    }

    protected function log(User $actor, Issue $issue, string $action, array $meta = []): Activity
    {
        return Activity::create([
            'workspace_id' => $issue->board->project->workspace_id,
            'actor_id' => $actor->id,
            'subject_type' => $issue::class,
            'subject_id' => $issue->id,
            'action' => $action,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
