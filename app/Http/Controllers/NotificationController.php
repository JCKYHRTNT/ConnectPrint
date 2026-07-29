<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = User::findOrFail(session('user_id'));
        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(20)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($this->cursorPayload($notifications));
        }

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function read(AppNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) session('user_id'), 403);
        $notification->update(['read_at' => now()]);
        return back();
    }

    public function readAll()
    {
        AppNotification::where('user_id', session('user_id'))->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    }

    private function cursorPayload($paginator): array
    {
        return [
            'data' => collect($paginator->items())
                ->map(fn ($notification) => view('notifications.partials.notification-row', ['notification' => $notification])->render())
                ->values()
                ->all(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'total' => null,
        ];
    }
}
