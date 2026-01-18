<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderBacklogRequest;
use App\Models\Issue;
use App\Models\Project;
use App\Services\IssueScheduler;
use App\Support\ApiResponse;

class BacklogController extends Controller
{
    public function reorder(ReorderBacklogRequest $request, Project $project, IssueScheduler $scheduler)
    {
        $this->authorize('update', $project);

        $data = $request->validated();
        $issue = Issue::findOrFail($data['issue_id']);

        $assignment = $scheduler->moveWithinBacklog($project, $issue, $data['to_position']);

        return ApiResponse::success(['backlog_item' => $assignment]);
    }
}
