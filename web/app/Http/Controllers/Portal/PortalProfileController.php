<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\StudentPortalNavigationService;
use App\Services\StudentPortalService;
use App\Services\StudentProfileChangeService;
use App\Services\StudentRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PortalProfileController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentProfileChangeService $profileChanges,
        protected StudentRecordService $studentRecords,
        protected StudentPortalNavigationService $navigation,
    ) {}

    public function edit(Request $request): View
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $biodata = $this->studentRecords->biodata360($student);

        return view('portal.profile.edit', [
            'student' => $student,
            'biodata' => $biodata,
            'sidebarNavigation' => $this->navigation->sidebarNavigation($student),
            'section' => 'profile',
            'tab' => null,
            'portalTitle' => 'Update profile',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $validated = $request->validate([
            'phone_number' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:191',
            'home_county' => 'nullable|string|max:120',
            'nationality' => 'nullable|string|max:100',
            'postal_address' => 'nullable|string|max:255',
            'next_of_kin_name' => 'nullable|string|max:191',
            'next_of_kin_relationship' => 'nullable|string|max:80',
            'next_of_kin_phone' => 'nullable|string|max:40',
            'next_of_kin_address' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:191',
            'emergency_contact_phone' => 'nullable|string|max:40',
            'emergency_contact_relationship' => 'nullable|string|max:80',
            'first_name' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'national_id_number' => 'nullable|string|max:40',
            'passport_number' => 'nullable|string|max:40',
            'student_notes' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo');
        }

        try {
            $result = $this->profileChanges->submit($student, $request->user(), $validated);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['profile' => $e->getMessage()]);
        }

        $parts = [];
        if ($result['applied'] !== []) {
            $parts[] = 'Contact details updated.';
        }
        if ($result['queued']) {
            $parts[] = 'Name / ID / date-of-birth changes were submitted for registrar approval.';
        }
        if ($result['photo_queued']) {
            $parts[] = 'Photo change was submitted for registrar approval.';
        }

        return redirect()
            ->route('portal.dashboard', ['section' => 'profile'])
            ->with('success', implode(' ', $parts) ?: 'Profile updated.');
    }
}
