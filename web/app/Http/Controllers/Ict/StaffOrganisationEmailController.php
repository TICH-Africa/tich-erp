<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffOrganisationEmailController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $validated = $request->validate([
            'organisation_email' => ['nullable', 'email', 'max:255', 'regex:/@tich\.africa$/i', 'unique:staff,organisation_email,'.$staff->id],
        ]);

        $organisationEmail = filled($validated['organisation_email'] ?? null)
            ? strtolower(trim($validated['organisation_email']))
            : null;

        $oldEmail = $staff->organisation_email;

        $staff->update(['organisation_email' => $organisationEmail]);
        $staff->syncLinkedUserEmail();

        $this->auditService->log(
            'staff.organisation_email.updated',
            'staff',
            $staff->id,
            ['organisation_email' => $oldEmail],
            ['organisation_email' => $organisationEmail],
            $organisationEmail ? 'Organisation email assigned' : 'Organisation email cleared',
            'success',
            $request->user()->id,
            $request,
        );

        return back()->with(
            'success',
            $organisationEmail
                ? 'Organisation email saved for '.$staff->fullName().'.'
                : 'Organisation email cleared for '.$staff->fullName().'.',
        );
    }
}
