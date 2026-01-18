<?php

namespace App\Services;

use App\Models\Issue;
use Illuminate\Support\Facades\DB;

class IssueMover
{
    public function move(Issue $issue, string $toColumnId, int $toPosition): Issue
    {
        return DB::transaction(function () use ($issue, $toColumnId, $toPosition) {
            $boardId = $issue->board_id;
            $fromColumnId = $issue->column_id;
            $fromPosition = $issue->position;

            if ($fromColumnId === $toColumnId) {
                $maxPosition = Issue::where('board_id', $boardId)
                    ->where('column_id', $toColumnId)
                    ->max('position') ?? 0;

                $targetPosition = max(1, min($toPosition, $maxPosition));

                if ($targetPosition === $fromPosition) {
                    return $issue->fresh();
                }

                if ($targetPosition < $fromPosition) {
                    Issue::where('board_id', $boardId)
                        ->where('column_id', $toColumnId)
                        ->whereBetween('position', [$targetPosition, $fromPosition - 1])
                        ->increment('position');
                } else {
                    Issue::where('board_id', $boardId)
                        ->where('column_id', $toColumnId)
                        ->whereBetween('position', [$fromPosition + 1, $targetPosition])
                        ->decrement('position');
                }

                $issue->update(['position' => $targetPosition]);

                return $issue->fresh();
            }

            $targetCount = Issue::where('board_id', $boardId)
                ->where('column_id', $toColumnId)
                ->count();

            $targetPosition = max(1, min($toPosition, $targetCount + 1));

            Issue::where('board_id', $boardId)
                ->where('column_id', $fromColumnId)
                ->where('position', '>', $fromPosition)
                ->decrement('position');

            Issue::where('board_id', $boardId)
                ->where('column_id', $toColumnId)
                ->where('position', '>=', $targetPosition)
                ->increment('position');

            $issue->update([
                'column_id' => $toColumnId,
                'position' => $targetPosition,
            ]);

            return $issue->fresh();
        });
    }
}
