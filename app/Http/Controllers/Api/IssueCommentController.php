<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Issue;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Support\ApiResponse;

class IssueCommentController extends Controller
{
    public function index(Issue $issue)
    {
        $this->authorize('view', $issue);

        $comments = $issue->comments()
            ->with('author')
            ->orderBy('created_at')
            ->paginate(20);

        return ApiResponse::success(['comments' => $comments]);
    }

    public function store(StoreCommentRequest $request, Issue $issue, ActivityLogger $activityLogger, NotificationService $notifications)
    {
        $comment = new Comment(['issue_id' => $issue->id]);
        $comment->setRelation('issue', $issue);

        $this->authorize('create', $comment);

        $data = $request->validated();
        $created = $issue->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $activityLogger->issueCommented($request->user(), $created);
        $notifications->issueCommented($issue, $request->user());

        return ApiResponse::success(['comment' => $created], 201);
    }
}
