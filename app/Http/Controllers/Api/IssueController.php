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
use App\Services\IssueScheduler;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use App\Support\WorkspaceAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IssueController extends Controller
{
    public function index(Board $board)
    {
        $this->authorize('view', $board);

        $query = $board->issues()
            ->with(['assignee', 'labels']);

        $this->applyFilters($query, request()->query());

        $sort = request()->query('sort', 'position');
        $direction = strtolower((string) request()->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['position', 'updated_at', 'due_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'position';
        }

        if ($sort === 'position') {
            $query->orderBy('column_id')->orderBy('position', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $issues = $query->get()->groupBy('column_id');

        $columnsQuery = $board->columns()->orderBy('position');
        $columnId = request()->query('column_id');
        if ($columnId) {
            $columnsQuery->where('id', $columnId);
        }

        $columns = $columnsQuery->get()->map(function ($column) use ($issues) {
            $column->setRelation('issues', $issues->get($column->id, collect())->values());
            return $column;
        });

        return ApiResponse::success(['columns' => $columns]);
    }

    public function store(StoreIssueRequest $request, Board $board, ActivityLogger $activityLogger, IssueScheduler $scheduler)
    {
        $this->authorize('create', $board);

        $data = $request->validated();
        $columnId = $data['column_id'] ?? $board->columns()->orderBy('position')->value('id');

        if (! $columnId) {
            return ApiResponse::error('Board has no columns.', 'no_columns', 422);
        }

        $column = $board->columns()->where('id', $columnId)->first();
        if (! $column) {
            return ApiResponse::error('Column not found on board.', 'column_not_found', 404);
        }

        if (! $this->canAcceptIssue($column->id, $column->wip_limit)) {
            return ApiResponse::error('WIP limit reached.', 'wip_limit_reached', 422);
        }

        $position = $board->issues()
            ->where('column_id', $columnId)
            ->max('position');

        $issue = $board->issues()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'column_id' => $columnId,
            'position' => ($position ?? 0) + 1,
            'priority' => $data['priority'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ]);

        $activityLogger->issueCreated($request->user(), $issue);
        $scheduler->assignToBacklog($board->project, $issue);

        return ApiResponse::success(['issue' => $issue], 201);
    }

    public function update(UpdateIssueRequest $request, Issue $issue, IssueMover $issueMover, ActivityLogger $activityLogger, NotificationService $notifications)
    {
        $this->authorize('update', $issue);

        $requestId = $request->attributes->get('request_id');
        $user = $request->user();
        if ($requestId && $user->last_issue_update_request_id === $requestId) {
            return ApiResponse::success(['issue' => $issue->fresh()]);
        }

        $data = $request->validated();
        $moved = false;
        $fromColumnId = $issue->column_id;
        $fromPosition = $issue->position;

        if (array_key_exists('column_id', $data) && $data['column_id'] !== $issue->column_id) {
            $targetColumn = $issue->board->columns()->where('id', $data['column_id'])->first();
            if (! $targetColumn) {
                return ApiResponse::error('Column not found on board.', 'column_not_found', 404);
            }

            if (! $this->canAcceptIssue($targetColumn->id, $targetColumn->wip_limit)) {
                return ApiResponse::error('WIP limit reached.', 'wip_limit_reached', 422);
            }

            $targetPosition = ($issue->board->issues()
                ->where('column_id', $data['column_id'])
                ->count()) + 1;

            $issue = $issueMover->move($issue, $data['column_id'], $targetPosition);
            $moved = true;
        }

        unset($data['column_id']);

        if (! empty($data)) {
            $issue->update($data);
        }

        if ($moved) {
            $activityLogger->issueMoved($request->user(), $issue, [
                'from_column_id' => $fromColumnId,
                'to_column_id' => $issue->column_id,
                'from_position' => $fromPosition,
                'to_position' => $issue->position,
            ]);

            $issue->load('column');
            if ($issue->column?->key === 'done') {
                $notifications->issueMovedToDone($issue, $request->user());
            }
        }

        if ($requestId) {
            $user->update(['last_issue_update_request_id' => $requestId]);
        }

        return ApiResponse::success(['issue' => $issue->fresh()]);
    }

    public function move(MoveIssueRequest $request, Issue $issue, IssueMover $issueMover, ActivityLogger $activityLogger, NotificationService $notifications)
    {
        $this->authorize('move', $issue);

        $requestId = $request->attributes->get('request_id');
        $user = $request->user();
        if ($requestId && $user->last_issue_move_request_id === $requestId) {
            return ApiResponse::success(['issue' => $issue->fresh()]);
        }

        $data = $request->validated();
        $fromColumnId = $issue->column_id;
        $fromPosition = $issue->position;

        $targetColumn = $issue->board->columns()->where('id', $data['to_column_id'])->first();
        if (! $targetColumn) {
            return ApiResponse::error('Column not found on board.', 'column_not_found', 404);
        }

        if ($targetColumn->id !== $issue->column_id && ! $this->canAcceptIssue($targetColumn->id, $targetColumn->wip_limit)) {
            return ApiResponse::error('WIP limit reached.', 'wip_limit_reached', 422);
        }

        $issue = $issueMover->move($issue, $data['to_column_id'], $data['to_position']);

        if ($fromColumnId !== $issue->column_id || $fromPosition !== $issue->position) {
            $activityLogger->issueMoved($request->user(), $issue, [
                'from_column_id' => $fromColumnId,
                'to_column_id' => $issue->column_id,
                'from_position' => $fromPosition,
                'to_position' => $issue->position,
            ]);

            $issue->load('column');
            if ($issue->column?->key === 'done') {
                $notifications->issueMovedToDone($issue, $request->user());
            }
        }

        if ($requestId) {
            $user->update(['last_issue_move_request_id' => $requestId]);
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

        if ($assigneeId) {
            $assignee = User::find($assigneeId);
            if ($assignee) {
                app(NotificationService::class)->issueAssigned($assignee, $issue, $request->user());
            }
        }

        return ApiResponse::success(['issue' => $issue->fresh()]);
    }

    public function destroy(Issue $issue, ActivityLogger $activityLogger)
    {
        $this->authorize('delete', $issue);

        $activityLogger->issueDeleted(request()->user(), $issue);

        $issue->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    protected function canAcceptIssue(string $columnId, ?int $wipLimit): bool
    {
        if (! $wipLimit) {
            return true;
        }

        $count = Issue::where('column_id', $columnId)->count();

        return $count < $wipLimit;
    }

    protected function applyFilters(Builder|Relation $query, array $params): void
    {
        if (! empty($params['q'])) {
            $search = trim((string) $params['q']);
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($params['column_id'])) {
            $query->where('column_id', $params['column_id']);
        }

        if (! empty($params['assignee_id'])) {
            $query->where('assignee_id', $params['assignee_id']);
        }

        if (! empty($params['label_id'])) {
            $query->whereHas('labels', function (Builder $builder) use ($params) {
                $builder->where('labels.id', $params['label_id']);
            });
        }

        if (! empty($params['priority'])) {
            $query->where('priority', $params['priority']);
        }

        if (! empty($params['due_from'])) {
            $query->where('due_at', '>=', $params['due_from']);
        }

        if (! empty($params['due_to'])) {
            $query->where('due_at', '<=', $params['due_to']);
        }
    }
}
