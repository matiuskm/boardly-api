<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\IssueSprint;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Support\Facades\DB;

class IssueScheduler
{
    public function assignToBacklog(Project $project, Issue $issue, ?int $toPosition = null): IssueSprint
    {
        return DB::transaction(function () use ($project, $issue, $toPosition) {
            $this->ensureProjectIssue($project, $issue);

            $current = IssueSprint::where('issue_id', $issue->id)->first();
            $currentSprintId = $current?->sprint_id;

            $maxPosition = $this->backlogQuery($issue->board_id)
                ->max('issue_sprint.position') ?? 0;

            $targetPosition = $toPosition ?? ($maxPosition + 1);
            $targetPosition = max(1, min($targetPosition, $maxPosition + 1));

            if ($currentSprintId === null && $current) {
                return $this->moveWithinBacklog($project, $issue, $targetPosition);
            }

            if ($current) {
                $this->closeGap($currentSprintId, $current->position, $issue->board_id);
                $current->update(['sprint_id' => null, 'position' => $targetPosition]);
            } else {
                IssueSprint::create([
                    'issue_id' => $issue->id,
                    'sprint_id' => null,
                    'position' => $targetPosition,
                ]);
            }

            $this->backlogQuery($issue->board_id)
                ->where('issue_sprint.issue_id', '!=', $issue->id)
                ->where('issue_sprint.position', '>=', $targetPosition)
                ->increment('issue_sprint.position');

            return IssueSprint::where('issue_id', $issue->id)->firstOrFail();
        });
    }

    public function assignToSprint(Sprint $sprint, Issue $issue, ?int $toPosition = null): IssueSprint
    {
        return DB::transaction(function () use ($sprint, $issue, $toPosition) {
            $this->ensureProjectIssue($sprint->project, $issue);

            $current = IssueSprint::where('issue_id', $issue->id)->first();
            $currentSprintId = $current?->sprint_id;

            $maxPosition = IssueSprint::where('sprint_id', $sprint->id)->max('position') ?? 0;
            $targetPosition = $toPosition ?? ($maxPosition + 1);
            $targetPosition = max(1, min($targetPosition, $maxPosition + 1));

            if ($currentSprintId === $sprint->id && $current) {
                return $this->moveWithinSprint($sprint, $issue, $targetPosition);
            }

            if ($current) {
                $this->closeGap($currentSprintId, $current->position, $issue->board_id);
                $current->update(['sprint_id' => $sprint->id, 'position' => $targetPosition]);
            } else {
                IssueSprint::create([
                    'issue_id' => $issue->id,
                    'sprint_id' => $sprint->id,
                    'position' => $targetPosition,
                ]);
            }

            IssueSprint::where('sprint_id', $sprint->id)
                ->where('issue_id', '!=', $issue->id)
                ->where('position', '>=', $targetPosition)
                ->increment('position');

            return IssueSprint::where('issue_id', $issue->id)->firstOrFail();
        });
    }

    public function moveWithinBacklog(Project $project, Issue $issue, int $toPosition): IssueSprint
    {
        return DB::transaction(function () use ($project, $issue, $toPosition) {
            $this->ensureProjectIssue($project, $issue);

            $current = IssueSprint::where('issue_id', $issue->id)->first();
            if (! $current || $current->sprint_id !== null) {
                return $this->assignToBacklog($project, $issue, $toPosition);
            }

            $maxPosition = $this->backlogQuery($issue->board_id)
                ->max('issue_sprint.position') ?? 0;

            $targetPosition = max(1, min($toPosition, $maxPosition));

            if ($targetPosition === $current->position) {
                return $current->fresh();
            }

            $this->shiftPositions(null, $current->position, $targetPosition, $issue->board_id);

            $current->update(['position' => $targetPosition]);

            return $current->fresh();
        });
    }

    public function moveWithinSprint(Sprint $sprint, Issue $issue, int $toPosition): IssueSprint
    {
        return DB::transaction(function () use ($sprint, $issue, $toPosition) {
            $this->ensureProjectIssue($sprint->project, $issue);

            $current = IssueSprint::where('issue_id', $issue->id)->first();
            if (! $current || $current->sprint_id !== $sprint->id) {
                return $this->assignToSprint($sprint, $issue, $toPosition);
            }

            $maxPosition = IssueSprint::where('sprint_id', $sprint->id)->max('position') ?? 0;
            $targetPosition = max(1, min($toPosition, $maxPosition));

            if ($targetPosition === $current->position) {
                return $current->fresh();
            }

            $this->shiftPositions($sprint->id, $current->position, $targetPosition);

            $current->update(['position' => $targetPosition]);

            return $current->fresh();
        });
    }

    protected function shiftPositions(?string $sprintId, int $fromPosition, int $toPosition, ?string $boardId = null): void
    {
        if ($sprintId === null && $boardId) {
            $query = $this->backlogQuery($boardId);
        } else {
            $query = IssueSprint::where('sprint_id', $sprintId);
        }

        if ($toPosition < $fromPosition) {
            $query->whereBetween('issue_sprint.position', [$toPosition, $fromPosition - 1])
                ->increment('issue_sprint.position');
        } else {
            $query->whereBetween('issue_sprint.position', [$fromPosition + 1, $toPosition])
                ->decrement('issue_sprint.position');
        }
    }

    protected function closeGap(?string $sprintId, int $fromPosition, ?string $boardId = null): void
    {
        if ($sprintId === null && $boardId) {
            $this->backlogQuery($boardId)
                ->where('issue_sprint.position', '>', $fromPosition)
                ->decrement('issue_sprint.position');
            return;
        }

        IssueSprint::where('sprint_id', $sprintId)
            ->where('position', '>', $fromPosition)
            ->decrement('position');
    }

    protected function backlogQuery(string $boardId)
    {
        return IssueSprint::whereNull('sprint_id')
            ->join('issues', 'issue_sprint.issue_id', '=', 'issues.id')
            ->where('issues.board_id', $boardId);
    }

    protected function ensureProjectIssue(Project $project, Issue $issue): void
    {
        if ($issue->board->project_id !== $project->id) {
            throw new \InvalidArgumentException('Issue does not belong to project.');
        }
    }
}
