<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('issue_sprint')) {
            return;
        }

        $issues = DB::table('issues')
            ->select('id', 'board_id', 'created_at')
            ->orderBy('created_at')
            ->get()
            ->groupBy('board_id');

        foreach ($issues as $boardId => $boardIssues) {
            $position = 1;
            foreach ($boardIssues as $issue) {
                $exists = DB::table('issue_sprint')
                    ->where('issue_id', $issue->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('issue_sprint')->insert([
                    'issue_id' => $issue->id,
                    'sprint_id' => null,
                    'position' => $position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $position++;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: historical ordering data should be preserved.
    }
};
