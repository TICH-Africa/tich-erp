<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentPortalInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentInviteController extends Controller
{
    public function __construct(
        protected StudentPortalInviteService $invites,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('ict.student-invites.index', [
            'search' => $search,
            'students' => $search === '' ? $this->invites->pendingActivations() : $this->invites->search($search),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return $this->flashInviteResult(
            $this->invites->sendToEmail($validated['email'], $request->user())
        );
    }

    public function resend(Request $request, Student $student): RedirectResponse
    {
        return $this->flashInviteResult(
            $this->invites->sendForStudent($student, $request->user())
        );
    }

    /**
     * @param  array{success: bool, message: string}  $result
     */
    private function flashInviteResult(array $result): RedirectResponse
    {
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
