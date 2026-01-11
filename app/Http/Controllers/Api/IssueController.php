<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignIssueRequest;
use App\Http\Requests\MoveIssueRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\IssueMover;
use App\Support\ApiResponse;
use App\Support\WorkspaceAccess;

class IssueController extends Controller
{
    public function index(Board $board)
    {
        $this->authorize('view', $board);

        $issues = $board->issues()
            ->orderBy('status')
            ->orderBy('position')
            ->get();

        return ApiResponse::success(['issues' => $issues]);
    }

    public function store(StoreIssueRequest $request, Board $board, ActivityLogger $activityLogger)
    {
        $this->authorize('create', $board);

        $data = $request->validated();
        $status = $data['status'] ?? 'todo';

        $position = $board->issues()
            ->where('status', $status)
            ->max('position');

        $issue = $board->issues()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'position' => ($position ?? 0) + 1,
        ]);

        $activityLogger->issueCreated($request->user(), $issue);

        return ApiResponse::success(['issue' => $issue], 201);
    }

    public function update(UpdateIssueRequest $request, Issue $issue, IssueMover $issueMover, ActivityLogger $activityLogger)
    {
        $this->authorize('update', $issue);

        $data = $request->validated();
        $status = $data['status'] ?? $issue->status;
        $moved = false;
        $fromStatus = $issue->status;
        $fromPosition = $issue->position;

        if (array_key_exists('status', $data) && $status !== $issue->status) {
            $targetPosition = ($issue->board->issues()
                ->where('status', $status)
                ->count()) + 1;

            $issue = $issueMover->move($issue, $status, $targetPosition);
            $moved = true;
        }

        unset($data['status']);

        if (! empty($data)) {
            $issue->update($data);
        }

        if ($moved) {
            $activityLogger->issueMoved($request->user(), $issue, [
                'from' => $fromStatus,
                'to' => $issue->status,
                'from_position' => $fromPosition,
                'to_position' => $issue->position,
            ]);
        }

        return ApiResponse::success(['issue' => $issue->fresh()]);
    }

    public function move(MoveIssueRequest $request, Issue $issue, IssueMover $issueMover, ActivityLogger $activityLogger)
    {
        $this->authorize('move', $issue);

        $data = $request->validated();
        $fromStatus = $issue->status;
        $fromPosition = $issue->position;

        $issue = $issueMover->move($issue, $data['to_status'], $data['to_position']);

        if ($fromStatus !== $issue->status || $fromPosition !== $issue->position) {
            $activityLogger->issueMoved($request->user(), $issue, [
                'from' => $fromStatus,
                'to' => $issue->status,
                'from_position' => $fromPosition,
                'to_position' => $issue->position,
            ]);
        }

        return ApiResponse::success(['issue' => $issue]);
    }

    public function assign(AssignIssueRequest $request, Issue $issue, ActivityLogger $activityLogger)
    {
        $this->authorize('assign', $issue);

        $data = $request->validated();
        $assigneeId = $data['assignee_id'] ?? null;

        if ($assigneeId !== null) {
            $assignee = User::findOrFail($assigneeId);

            if (! WorkspaceAccess::isMember($assignee, $issue->board->project->workspace)) {
                return ApiResponse::error('Assignee must be a workspace member.', 'not_member', 422);
            }
        }

        $fromUserId = $issue->assignee_id;
        $issue->update(['assignee_id' => $assigneeId]);

        $activityLogger->issueAssigned($request->user(), $issue, $fromUserId, $assigneeId);

        return ApiResponse::success(['issue' => $issue->fresh()]);
    }

    public function destroy(Issue $issue, ActivityLogger $activityLogger)
    {
        $this->authorize('delete', $issue);

        $activityLogger->issueDeleted(request()->user(), $issue);

        $issue->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
