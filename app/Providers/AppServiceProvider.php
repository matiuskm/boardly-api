<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Workspace;
use App\Policies\WorkspacePolicy;
use App\Models\Project;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Label;
use App\Models\Issue;
use App\Policies\ActivityPolicy;
use App\Policies\BoardPolicy;
use App\Policies\BoardColumnPolicy;
use App\Policies\IssuePolicy;
use App\Policies\LabelPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Board::class, BoardPolicy::class);
        Gate::policy(BoardColumn::class, BoardColumnPolicy::class);
        Gate::policy(Issue::class, IssuePolicy::class);
        Gate::policy(Label::class, LabelPolicy::class);
    }
}
