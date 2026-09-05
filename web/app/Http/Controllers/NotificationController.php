<?php

namespace App\Http\Controllers;

use App\Models\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = InAppNotification::forUser((int) $user->id);
        $unreadCount = $notifications->filter(fn (InAppNotification $n) => $n->isUnread())->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function open(Request $request, InAppNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->markRead($request->userAgent());

        $target = $notification->actionUrl($request->user());

        if ($target) {
            return redirect()->to($target);
        }

        return redirect()
            ->route('notifications.index')
            ->with('status', 'Notification opened. No linked page was available for this alert.');
    }

    public function markRead(Request $request, InAppNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->markRead($request->userAgent());

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        InAppNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', 0)
            ->where('is_dismissed', 0)
            ->update([
                'is_read' => 1,
                'read_at' => now(),
                'read_device_info' => $request->userAgent(),
            ]);

        return redirect()
            ->route('notifications.index')
            ->with('success', 'All notifications marked as read.');
    }
}
