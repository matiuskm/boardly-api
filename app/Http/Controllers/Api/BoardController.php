<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBoardRequest;
use App\Models\Board;
use App\Models\Project;
use App\Support\ApiResponse;

class BoardController extends Controller
{
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $board = $project->board()->first();

        if (! $board) {
            return ApiResponse::error('Board not found.', 'not_found', 404);
        }

        $include = request()->query('include');
        if ($include && str_contains($include, 'issues')) {
            $issues = $board->issues()
                ->orderBy('position')
                ->get()
                ->groupBy('status');

            $columns = [
                'todo' => $issues->get('todo', collect())->values(),
                'doing' => $issues->get('doing', collect())->values(),
                'done' => $issues->get('done', collect())->values(),
            ];

            return ApiResponse::success(['columns' => $columns]);
        }

        return ApiResponse::success(['board' => $board]);
    }

    public function store(StoreBoardRequest $request, Project $project)
    {
        $this->authorize('create', $project);

        $existing = $project->board;
        if ($existing) {
            return ApiResponse::success(['board' => $existing]);
        }

        $board = $project->board()->create($request->validated());

        return ApiResponse::success(['board' => $board], 201);
    }
}
