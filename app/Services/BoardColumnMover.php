<?php

namespace App\Services;

use App\Models\Board;
use App\Models\BoardColumn;
use Illuminate\Support\Facades\DB;

class BoardColumnMover
{
    public function insert(Board $board, array $data): BoardColumn
    {
        return DB::transaction(function () use ($board, $data) {
            $maxPosition = BoardColumn::where('board_id', $board->id)
                ->max('position') ?? 0;

            $position = $data['position'] ?? ($maxPosition + 1);
            $targetPosition = max(1, min($position, $maxPosition + 1));

            if ($targetPosition <= $maxPosition) {
                BoardColumn::where('board_id', $board->id)
                    ->where('position', '>=', $targetPosition)
                    ->increment('position');
            }

            return $board->columns()->create([
                'name' => $data['name'],
                'key' => $data['key'],
                'position' => $targetPosition,
                'wip_limit' => $data['wip_limit'] ?? null,
            ]);
        });
    }

    public function move(BoardColumn $column, int $toPosition): BoardColumn
    {
        return DB::transaction(function () use ($column, $toPosition) {
            $boardId = $column->board_id;
            $fromPosition = $column->position;

            $maxPosition = BoardColumn::where('board_id', $boardId)
                ->max('position') ?? 0;

            $targetPosition = max(1, min($toPosition, $maxPosition));

            if ($targetPosition === $fromPosition) {
                return $column->fresh();
            }

            if ($targetPosition < $fromPosition) {
                BoardColumn::where('board_id', $boardId)
                    ->whereBetween('position', [$targetPosition, $fromPosition - 1])
                    ->increment('position');
            } else {
                BoardColumn::where('board_id', $boardId)
                    ->whereBetween('position', [$fromPosition + 1, $targetPosition])
                    ->decrement('position');
            }

            $column->update(['position' => $targetPosition]);

            return $column->fresh();
        });
    }

    public function remove(BoardColumn $column): void
    {
        DB::transaction(function () use ($column) {
            $boardId = $column->board_id;
            $position = $column->position;

            $column->delete();

            BoardColumn::where('board_id', $boardId)
                ->where('position', '>', $position)
                ->decrement('position');
        });
    }
}
