<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\StudentEnrollmentService;
use App\Services\StudentPortalService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

class PortalActivationController extends Controller
{
    public function __construct(
        protected StudentEnrollmentService $enrollmentService,
        protected StudentPortalService $portalService,
        protected AuthService $authService,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $student = $this->enrollmentService->findByPortalToken($token);

        if (! $student) {
            abort(404, 'Activation link is invalid or has already been used.');
        }

        if ($student->portal_activated_at || $student->user_id) {
            return redirect()
                ->route('login')
                ->with('status', 'Your student portal is already active. Sign in with your username and password.');
        }

        if ($student->portal_invite_expires_at?->isPast()) {
            abort(410, 'This activation link has expired. Contact the admissions office for assistance.');
        }

        $applicant = $student->applicant;
        $suggestedUsername = $this->suggestUsername($applicant?->email ?? '');

        return view('portal.activate', [
            'student' => $student,
            'applicant' => $applicant,
            'suggestedUsername' => $suggestedUsername,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $student = $this->enrollmentService->findByPortalToken($token);

        if (! $student) {
            abort(404);
        }

        $existingUserId = User::query()
            ->where('email', $student->applicant?->email)
            ->value('id');

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($existingUserId)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $this->portalService->activatePortalAccount($student, $validated);

        $destination = $this->authService->postLoginDestination($request->user(), $request);

        return redirect()
            ->to($destination)
            ->with('status', 'Welcome to the TICH student portal. Your account is ready.');
    }

    private function suggestUsername(string $email): string
    {
        $local = strtolower(strtok($email, '@') ?: 'student');
        $local = preg_replace('/[^a-z0-9._-]/', '', $local) ?: 'student';

        return substr($local, 0, 50);
    }
}
