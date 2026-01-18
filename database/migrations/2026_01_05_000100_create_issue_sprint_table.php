<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('issue_sprint', function (Blueprint $table) {
            $table->foreignUuid('issue_id')->primary()->constrained('issues')->cascadeOnDelete();
            $table->foreignUuid('sprint_id')->nullable()->constrained('sprints')->nullOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['sprint_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_sprint');
    }
};
