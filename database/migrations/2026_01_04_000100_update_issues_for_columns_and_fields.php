<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->foreignUuid('column_id')
                ->nullable()
                ->after('board_id')
                ->constrained('board_columns')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('priority')->nullable()->after('description');
            $table->dateTime('due_at')->nullable()->after('priority');

            $table->index('column_id');
            $table->index(['column_id', 'position']);
            $table->index('priority');
            $table->index('due_at');
        });

        $boards = DB::table('boards')->select('id')->get();

        foreach ($boards as $board) {
            $existing = DB::table('board_columns')
                ->where('board_id', $board->id)
                ->pluck('id', 'key')
                ->all();

            $columns = $this->ensureDefaultColumns($board->id, $existing);

            foreach (['todo', 'doing', 'done'] as $key) {
                if (! isset($columns[$key])) {
                    continue;
                }

                DB::table('issues')
                    ->where('board_id', $board->id)
                    ->where('status', $key)
                    ->update(['column_id' => $columns[$key]]);
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE issues MODIFY column_id CHAR(36) NOT NULL');
        }

        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['board_id', 'status', 'position']);
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->enum('status', ['todo', 'doing', 'done'])->default('todo')->after('description');
        });

        $issues = DB::table('issues')->select('id', 'column_id')->get();
        $columns = DB::table('board_columns')->pluck('key', 'id')->all();

        foreach ($issues as $issue) {
            $status = $columns[$issue->column_id] ?? 'todo';
            DB::table('issues')->where('id', $issue->id)->update(['status' => $status]);
        }

        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['column_id', 'position']);
            $table->dropIndex(['column_id']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['due_at']);
            $table->dropForeign(['column_id']);
            $table->dropColumn(['column_id', 'priority', 'due_at']);
            $table->index(['board_id', 'status', 'position']);
        });
    }

    /**
     * @param array<string, string> $existing
     * @return array<string, string>
     */
    protected function ensureDefaultColumns(string $boardId, array $existing): array
    {
        $defaults = [
            ['key' => 'todo', 'name' => 'Todo', 'position' => 1],
            ['key' => 'doing', 'name' => 'Doing', 'position' => 2],
            ['key' => 'done', 'name' => 'Done', 'position' => 3],
        ];

        $columns = $existing;

        foreach ($defaults as $column) {
            if (isset($columns[$column['key']])) {
                continue;
            }

            $id = (string) Str::uuid();
            DB::table('board_columns')->insert([
                'id' => $id,
                'board_id' => $boardId,
                'name' => $column['name'],
                'key' => $column['key'],
                'position' => $column['position'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $columns[$column['key']] = $id;
        }

        return $columns;
    }
};
