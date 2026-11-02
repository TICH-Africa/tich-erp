<?php

namespace App\Http\Controllers\Api\Student\mobile\Concerns;

use App\Models\Student;
use App\Services\StudentPortalService;
use App\Services\StudentRecordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait ResolvesPortalStudent
{
    protected function portalStudent(Request $request): Student
    {
        $student = $request->attributes->get('portal_student');

        if ($student instanceof Student) {
            return $student;
        }

        /** @var StudentPortalService $portal */
        $portal = app(StudentPortalService::class);
        $student = $portal->studentForUser($request->user());

        abort_unless($student, 403, 'Student portal requires a linked student account.');

        $request->attributes->set('portal_student', $student);

        return $student;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mobileBiodata(Student $student): array
    {
        return Cache::remember(
            'student.mobile.biodata.'.$student->id.'.v1',
            45,
            fn () => app(StudentRecordService::class)->biodataForMobilePortal($student),
        );
    }
}
