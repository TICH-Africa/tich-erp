<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentNotification;
use App\Models\UnitContent;
use App\Services\Sidebar\PortalSidebarBroadcastService;
use App\Support\SchemaProbe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnitLearningContentNotificationService
{
    public function __construct(
        protected PlatformNotificationService $platformNotifications,
        protected PortalSidebarBroadcastService $sidebarBroadcast,
    ) {}

    public function notifyPublished(UnitContent $content): void
    {
        if ($content->status !== 'published') {
            return;
        }

        $content->loadMissing(['unit', 'unitAllocation']);

        $unitId = (int) $content->unit_id;
        $semesterId = $content->unitAllocation?->semester_id
            ? (int) $content->unitAllocation->semester_id
            : null;

        $students = $this->studentsEnrolledOnUnit($unitId, $semesterId);
        if ($students->isEmpty()) {
            return;
        }

        $unitCode = $content->unit?->unit_code;
        $unitName = $content->unit?->unit_name;
        $unitLabel = trim(collect([$unitCode, $unitName])->filter()->implode(' · ')) ?: 'your unit';
        $title = 'New learning material';
        $body = "New material for {$unitLabel}: {$content->title}.";
        $actionUrl = '/portal?section=academics&tab=content';
        $entityId = (string) $content->id;

        foreach ($students as $row) {
            $studentId = (int) $row->student_id;
            $userId = $row->user_id ? (int) $row->user_id : null;

            if (SchemaProbe::hasTable('student_notifications')) {
                StudentNotification::query()->create([
                    'student_id' => $studentId,
                    'category' => 'academics',
                    'title' => $title,
                    'body' => $body,
                    'action_url' => $actionUrl,
                ]);
            }

            if ($userId) {
                $this->platformNotifications->notifyUser(
                    $userId,
                    $title,
                    $body,
                    'unit_content',
                    $entityId,
                    'normal',
                    $actionUrl,
                );
            }

            $student = Student::query()->find($studentId);
            if ($student) {
                $this->sidebarBroadcast->broadcastStudent($student);
            }
        }
    }

    /**
     * Students with an active registration on the unit (not the whole programme).
     *
     * @return Collection<int, object{student_id: int, user_id: int|null}>
     */
    private function studentsEnrolledOnUnit(int $unitId, ?int $semesterId): Collection
    {
        if (! SchemaProbe::hasTable('registered_units') || ! SchemaProbe::hasTable('student_semester_registrations')) {
            return collect();
        }

        $query = DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->join('students as st', 'st.id', '=', 'ssr.student_id')
            ->where('ru.unit_id', $unitId)
            ->where('st.is_active', 1)
            ->whereIn('st.enrollment_status', ['active', 'enrolled', 'registered']);

        if ($semesterId !== null) {
            $query->where('ssr.semester_id', $semesterId);
        }

        return $query
            ->select(['st.id as student_id', 'st.user_id'])
            ->distinct()
            ->get();
    }
}
