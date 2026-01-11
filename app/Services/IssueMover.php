<?php

namespace App\Services;

use App\Models\Issue;
use Illuminate\Support\Facades\DB;

class IssueMover
{
    public function move(Issue $issue, string $toStatus, int $toPosition): Issue
    {
        return DB::transaction(function () use ($issue, $toStatus, $toPosition) {
            $boardId = $issue->board_id;
            $fromStatus = $issue->status;
            $fromPosition = $issue->position;

            if ($fromStatus === $toStatus) {
                $maxPosition = Issue::where('board_id', $boardId)
                    ->where('status', $toStatus)
                    ->max('position') ?? 0;

                $targetPosition = max(1, min($toPosition, $maxPosition));

                if ($targetPosition === $fromPosition) {
                    return $issue->fresh();
                }

                if ($targetPosition < $fromPosition) {
                    Issue::where('board_id', $boardId)
                        ->where('status', $toStatus)
                        ->whereBetween('position', [$targetPosition, $fromPosition - 1])
                        ->increment('position');
                } else {
                    Issue::where('board_id', $boardId)
                        ->where('status', $toStatus)
                        ->whereBetween('position', [$fromPosition + 1, $targetPosition])
                        ->decrement('position');
                }

                $issue->update(['position' => $targetPosition]);

                return $issue->fresh();
            }

            $targetCount = Issue::where('board_id', $boardId)
                ->where('status', $toStatus)
                ->count();

            $targetPosition = max(1, min($toPosition, $targetCount + 1));

            Issue::where('board_id', $boardId)
                ->where('status', $fromStatus)
                ->where('position', '>', $fromPosition)
                ->decrement('position');

            Issue::where('board_id', $boardId)
                ->where('status', $toStatus)
                ->where('position', '>=', $targetPosition)
                ->increment('position');

            $issue->update([
                'status' => $toStatus,
                'position' => $targetPosition,
            ]);

            return $issue->fresh();
        });
    }
}
