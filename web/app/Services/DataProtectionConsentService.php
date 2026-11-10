<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\PersonalDataConsentRecord;
use App\Support\KenyaDataProtectionNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DataProtectionConsentService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function recordApplicantApplicationConsent(Applicant $applicant, Request $request): void
    {
        if (! Schema::hasTable('personal_data_consent_records')) {
            return;
        }

        $purpose = config('data-protection.consent_purposes.admissions_application', []);
        $version = KenyaDataProtectionNotice::policyVersion();
        $consentedAt = now();

        PersonalDataConsentRecord::create([
            'subject_type' => 'applicant',
            'subject_id' => $applicant->id,
            'subject_email' => $applicant->email,
            'purpose_key' => 'admissions_application',
            'lawful_basis' => $purpose['lawful_basis'] ?? 'consent',
            'privacy_notice_version' => $version,
            'terms_version' => KenyaDataProtectionNotice::policyVersion(),
            'consented' => true,
            'consent_method' => 'checkbox',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'channel' => $purpose['channel'] ?? 'web_apply',
            'consented_at' => $consentedAt,
        ]);

        if (Schema::hasColumn('applicants', 'privacy_notice_version')) {
            $applicant->forceFill([
                'privacy_notice_version' => $version,
                'privacy_consented_at' => $consentedAt,
            ])->saveQuietly();
        }

        $this->auditService->log(
            'data_protection.consent.recorded',
            'applicants',
            $applicant->id,
            null,
            [
                'purpose_key' => 'admissions_application',
                'privacy_notice_version' => $version,
                'lawful_basis' => 'consent',
            ],
            'Applicant privacy notice consent at submission',
            'success',
            null,
            $request,
        );
    }
}
