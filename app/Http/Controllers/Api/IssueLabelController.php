<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignIssueLabelsRequest;
use App\Models\Issue;
use App\Models\Label;
use App\Support\ApiResponse;

class IssueLabelController extends Controller
{
    public function store(AssignIssueLabelsRequest $request, Issue $issue)
    {
        $this->authorize('update', $issue);

        $labelIds = $request->validated()['label_ids'];

        $labels = Label::whereIn('id', $labelIds)->get();
        $workspaceId = $issue->board->project->workspace_id;

        if ($labels->count() !== count($labelIds)) {
            return ApiResponse::error('Some labels were not found.', 'labels_not_found', 404);
        }

        if ($labels->contains(fn (Label $label) => $label->workspace_id !== $workspaceId)) {
            return ApiResponse::error('Labels must belong to the same workspace.', 'cross_workspace', 422);
        }

        $issue->labels()->syncWithoutDetaching($labelIds);

        return ApiResponse::success(['labels' => $issue->labels()->orderBy('name')->get()]);
    }

    public function destroy(Issue $issue, Label $label)
    {
        $this->authorize('update', $issue);

        if ($label->workspace_id !== $issue->board->project->workspace_id) {
            return ApiResponse::error('Label not found in workspace.', 'cross_workspace', 404);
        }

        $issue->labels()->detach($label->id);

        return ApiResponse::success(['deleted' => true]);
    }
}
