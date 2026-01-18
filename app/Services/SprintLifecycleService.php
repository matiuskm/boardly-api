<?php

namespace App\Services;

use App\Models\Sprint;
use Illuminate\Support\Facades\DB;

class SprintLifecycleService
{
    public function start(Sprint $sprint): Sprint
    {
        return DB::transaction(function () use ($sprint) {
            $sprint->project->sprints()
                ->where('status', 'active')
                ->where('id', '!=', $sprint->id)
                ->update(['status' => 'completed', 'ends_at' => now()]);

            $sprint->update([
                'status' => 'active',
                'starts_at' => $sprint->starts_at ?? now(),
            ]);

            return $sprint->fresh();
        });
    }

    public function complete(Sprint $sprint): Sprint
    {
        $sprint->update([
            'status' => 'completed',
            'ends_at' => $sprint->ends_at ?? now(),
        ]);

        return $sprint->fresh();
    }
}
