<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Research\PartnershipInquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnershipInquiryController extends Controller
{
    public function __construct(
        protected PartnershipInquiryService $inquiries,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'applicant_type' => ['required', 'in:organisation,individual'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'alternative_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternative_phone' => ['nullable', 'string', 'max:30'],
            'research_area' => ['nullable', 'string', 'max:200'],
            'research_area_other' => [
                Rule::requiredIf(fn () => $request->input('research_area') === 'Other'),
                'nullable',
                'string',
                'max:200',
            ],
            'organization_name' => ['nullable', 'required_if:applicant_type,organisation', 'string', 'max:300'],
            'organization_type' => ['nullable', 'required_if:applicant_type,organisation', 'string', 'max:100'],
            'organization_type_other' => [
                Rule::requiredIf(fn () => $request->input('applicant_type') === 'organisation'
                    && $request->input('organization_type') === 'other'),
                'nullable',
                'string',
                'max:100',
            ],
            'organisation_details' => ['nullable', 'required_if:applicant_type,organisation', 'string', 'max:5000'],
            'individual_details' => ['nullable', 'required_if:applicant_type,individual', 'string', 'max:5000'],
            'what_they_do' => ['required', 'string', 'max:5000'],
            'why_partnership' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,png,jpg,jpeg'],
        ]);

        if (($data['research_area'] ?? '') === 'Other') {
            $data['research_area'] = trim((string) ($data['research_area_other'] ?? ''));
        }

        if (($data['organization_type'] ?? '') === 'other') {
            $data['organization_type'] = trim((string) ($data['organization_type_other'] ?? ''));
        }

        unset($data['research_area_other'], $data['organization_type_other']);

        $files = $request->file('attachments', []) ?: [];
        $inquiry = $this->inquiries->submit($data, is_array($files) ? $files : [$files]);

        return redirect()
            ->route('research')
            ->with('status', 'Thank you. Your partnership inquiry '.$inquiry->request_number.' has been received. Our research team will contact you.');
    }
}
