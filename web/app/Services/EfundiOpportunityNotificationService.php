<?php

namespace App\Services;

use App\Models\EfundiOpportunity;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Services\Sidebar\PortalSidebarBroadcastService;
use App\Support\SchemaProbe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EfundiOpportunityNotificationService
{
    public function __construct(
        protected PlatformNotificationService $platformNotifications,
        protected PortalSidebarBroadcastService $sidebarBroadcast,
    ) {}

    public function notifyAllStudents(EfundiOpportunity $opportunity): int
    {
        if (! $opportunity->is_published) {
            return 0;
        }

        $title = 'New '.$opportunity->typeLabel().' on e-Fundi';
        $summary = $opportunity->summary ?: Str::limit(strip_tags($opportunity->description ?? ''), 160);
        $body = $opportunity->title.($summary ? ' — '.$summary : '');
        $actionUrl = route('e-fundi.show', $opportunity->slug);
        $entityId = (string) $opportunity->id;

        $count = 0;

        Student::query()
            ->where('is_active', 1)
            ->whereIn('enrollment_status', ['active', 'enrolled', 'registered'])
            ->whereNotNull('user_id')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(200, function ($students) use ($opportunity, $title, $body, $actionUrl, $entityId, &$count) {
                $notificationRows = [];

                foreach ($students as $student) {
                    $studentId = (int) $student->id;
                    $userId = (int) $student->user_id;

                    if (SchemaProbe::hasTable('student_notifications')) {
                        $notificationRows[] = [
                            'student_id' => $studentId,
                            'category' => 'efundi',
                            'title' => $title,
                            'body' => $body,
                            'action_url' => $actionUrl,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $this->platformNotifications->notifyUser(
                        $userId,
                        $title,
                        $body,
                        'efundi_opportunity',
                        $entityId,
                        'normal',
                        $actionUrl,
                        sendEmail: false,
                    );

                    $this->sidebarBroadcast->broadcastStudent($student);

                    $count++;
                }

                if ($notificationRows !== [] && SchemaProbe::hasTable('student_notifications')) {
                    DB::table('student_notifications')->insert($notificationRows);
                }
            });

        $opportunity->forceFill(['students_notified_at' => now()])->save();

        return $count;
    }
}