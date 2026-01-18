<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderSprintIssuesRequest;
use App\Models\Issue;
use App\Models\Sprint;
use App\Services\IssueScheduler;
use App\Support\ApiResponse;

class SprintIssueController extends Controller
{
    public function reorder(ReorderSprintIssuesRequest $request, Sprint $sprint, IssueScheduler $scheduler)
    {
        $this->authorize('update', $sprint);

        $data = $request->validated();
        $issue = Issue::findOrFail($data['issue_id']);

        $assignment = $scheduler->assignToSprint($sprint, $issue, $data['to_position']);

        return ApiResponse::success(['sprint_item' => $assignment]);
    }
}
