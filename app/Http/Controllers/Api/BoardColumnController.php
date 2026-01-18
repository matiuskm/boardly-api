<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBoardColumnRequest;
use App\Http\Requests\UpdateBoardColumnRequest;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Services\BoardColumnMover;
use App\Support\ApiResponse;
use Illuminate\Support\Str;

class BoardColumnController extends Controller
{
    public function index(Board $board)
    {
        $this->authorize('viewAny', [BoardColumn::class, $board]);

        $columns = $board->columns()
            ->orderBy('position')
            ->get();

        return ApiResponse::success(['columns' => $columns]);
    }

    public function store(StoreBoardColumnRequest $request, Board $board, BoardColumnMover $mover)
    {
        $this->authorize('create', [BoardColumn::class, $board]);

        $data = $request->validated();
        $key = $data['key'] ?? $this->makeKey($board, $data['name']);

        $column = $mover->insert($board, array_merge($data, ['key' => $key]));

        return ApiResponse::success(['column' => $column], 201);
    }

    public function update(UpdateBoardColumnRequest $request, BoardColumn $column, BoardColumnMover $mover)
    {
        $this->authorize('update', $column);

        $data = $request->validated();

        if (array_key_exists('position', $data)) {
            $column = $mover->move($column, $data['position']);
            unset($data['position']);
        }

        if (! empty($data)) {
            $column->update($data);
        }

        return ApiResponse::success(['column' => $column->fresh()]);
    }

    public function destroy(BoardColumn $column, BoardColumnMover $mover)
    {
        $this->authorize('delete', $column);

        if ($column->issues()->exists()) {
            return ApiResponse::error('Column has issues assigned.', 'column_not_empty', 422);
        }

        $mover->remove($column);

        return ApiResponse::success(['deleted' => true]);
    }

    protected function makeKey(Board $board, string $name): string
    {
        $base = Str::slug($name) ?: (string) Str::uuid();
        $key = $base;
        $counter = 1;

        while ($board->columns()->where('key', $key)->exists()) {
            $key = $base.'-'.$counter;
            $counter++;
        }

        return $key;
    }
}
