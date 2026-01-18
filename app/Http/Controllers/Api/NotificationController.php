<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\ApiResponse;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = request()->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(['notifications' => $notifications]);
    }

    public function read(Notification $notification)
    {
        $this->authorize('update', $notification);

        $notification->update(['read_at' => now()]);

        return ApiResponse::success(['notification' => $notification->fresh()]);
    }

    public function readAll()
    {
        $user = request()->user();
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return ApiResponse::success(['read' => true]);
    }
}
