<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Board;
use App\Models\Issue;
use App\Support\ApiResponse;

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

    public function store(StoreIssueRequest $request, Board $board)
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

        return ApiResponse::success(['issue' => $issue], 201);
    }

    public function update(UpdateIssueRequest $request, Issue $issue)
    {
        $this->authorize('update', $issue);

        $data = $request->validated();
        $status = $data['status'] ?? $issue->status;

        if (array_key_exists('status', $data) && $status !== $issue->status) {
            $position = $issue->board->issues()
                ->where('status', $status)
                ->max('position');
            $data['position'] = ($position ?? 0) + 1;
        }

        $issue->update($data);

        return ApiResponse::success(['issue' => $issue->fresh()]);
    }

    public function destroy(Issue $issue)
    {
        $this->authorize('delete', $issue);

        $issue->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
