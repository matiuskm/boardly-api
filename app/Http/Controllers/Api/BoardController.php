<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBoardRequest;
use App\Models\Board;
use App\Models\Project;
use App\Support\ApiResponse;
use Illuminate\Support\Collection;

class BoardController extends Controller
{
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $board = $project->board()->first();

        if (! $board) {
            return ApiResponse::error('Board not found.', 'not_found', 404);
        }

        $include = collect(explode(',', (string) request()->query('include')))
            ->filter()
            ->map(fn (string $item) => trim($item))
            ->values();

        if ($include->contains('columns') || $include->contains('issues')) {
            $columns = $this->loadBoardColumns($board, $include->contains('issues'));

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
        $this->seedDefaultColumns($board);

        return ApiResponse::success(['board' => $board], 201);
    }

    protected function loadBoardColumns(Board $board, bool $includeIssues): Collection
    {
        $columns = $board->columns()
            ->orderBy('position')
            ->get();

        if (! $includeIssues) {
            return $columns;
        }

        $issues = $board->issues()
            ->with(['assignee', 'labels'])
            ->orderBy('position')
            ->get()
            ->groupBy('column_id');

        return $columns->map(function ($column) use ($issues) {
            $column->setRelation('issues', $issues->get($column->id, collect())->values());
            return $column;
        });
    }

    protected function seedDefaultColumns(Board $board): void
    {
        $defaults = [
            ['name' => 'Todo', 'key' => 'todo', 'position' => 1],
            ['name' => 'Doing', 'key' => 'doing', 'position' => 2],
            ['name' => 'Done', 'key' => 'done', 'position' => 3],
        ];

        foreach ($defaults as $column) {
            $board->columns()->create($column);
        }
    }
}
