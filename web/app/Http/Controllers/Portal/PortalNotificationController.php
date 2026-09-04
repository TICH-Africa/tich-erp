<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\StudentNotification;
use App\Services\Sidebar\StudentSidebarNotificationService;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalNotificationController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentSidebarNotificationService $sidebarNotifications,
    ) {}

    public function markRead(Request $request, StudentNotification $notification): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);
        abort_unless((int) $notification->student_id === (int) $student->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
            $this->sidebarNotifications->forget($student);
        }

        if ($notification->action_url) {
            return redirect()->to($notification->action_url);
        }

        return redirect()
            ->route('portal.dashboard', ['section' => 'notifications'])
            ->with('success', 'Notification marked as read.');
    }
}
