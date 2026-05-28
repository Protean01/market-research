<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationFeedController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'data'       => $n->data,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return response()->json($notifications);
    }

    // Used by both web route (id in body) and API route (id as route param)
    public function markRead(Request $request, ?string $id = null)
    {
        $notificationId = $id ?? $request->validate(['id' => 'required|string'])['id'];

        $notification = $request->user()->notifications()->where('id', $notificationId)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
