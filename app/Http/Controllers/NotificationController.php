<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;

class NotificationController extends Controller
{
    public function index()
    {
        $user = User::findOrFail(session('user_id'));
        return view('notifications.index', [
            'notifications' => $user->notifications()->latest()->paginate(20),
        ]);
    }

    public function read(string $username, AppNotification $notification)
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
}
