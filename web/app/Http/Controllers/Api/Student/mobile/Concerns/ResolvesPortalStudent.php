<?php

namespace App\Http\Controllers\Api\Student\mobile\Concerns;

use App\Models\Student;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;

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
}
