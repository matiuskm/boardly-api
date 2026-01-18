<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

class NotificationService
{
    public function issueAssigned(User $assignee, Issue $issue, User $actor): ?Notification
    {
        if ($assignee->id === $actor->id) {
            return null;
        }

        return $this->create($assignee, 'issue.assigned', [
            'issue_id' => $issue->id,
            'board_id' => $issue->board_id,
            'actor_id' => $actor->id,
        ]);
    }

    public function issueMovedToDone(Issue $issue, User $actor): ?Notification
    {
        $assignee = $issue->assignee;
        if (! $assignee || $assignee->id === $actor->id) {
            return null;
        }

        return $this->create($assignee, 'issue.done', [
            'issue_id' => $issue->id,
            'board_id' => $issue->board_id,
            'actor_id' => $actor->id,
        ]);
    }

    public function issueCommented(Issue $issue, User $actor): ?Notification
    {
        $assignee = $issue->assignee;
        if (! $assignee || $assignee->id === $actor->id) {
            return null;
        }

        return $this->create($assignee, 'issue.commented', [
            'issue_id' => $issue->id,
            'board_id' => $issue->board_id,
            'actor_id' => $actor->id,
        ]);
    }

    /**
     * @return array<int, Notification>
     */
    public function sprintStarted(Sprint $sprint, User $actor): array
    {
        return $this->notifyProjectMembers($sprint->project, 'sprint.started', [
            'sprint_id' => $sprint->id,
            'project_id' => $sprint->project_id,
            'actor_id' => $actor->id,
        ]);
    }

    /**
     * @return array<int, Notification>
     */
    public function sprintCompleted(Sprint $sprint, User $actor): array
    {
        return $this->notifyProjectMembers($sprint->project, 'sprint.completed', [
            'sprint_id' => $sprint->id,
            'project_id' => $sprint->project_id,
            'actor_id' => $actor->id,
        ]);
    }

    protected function create(User $user, string $type, array $payload): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'payload' => $payload,
        ]);
    }

    /**
     * @return array<int, Notification>
     */
    protected function notifyProjectMembers(Project $project, string $type, array $payload): array
    {
        $members = $project->members()->get();

        if ($members->isEmpty()) {
            $members = $project->workspace->members()->get();
        }

        return $members->map(function (User $user) use ($type, $payload) {
            return $this->create($user, $type, $payload);
        })->all();
    }
}
