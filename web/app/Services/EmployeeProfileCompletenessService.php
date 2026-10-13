<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;

class EmployeeProfileCompletenessService
{
    /**
     * Contact / accountability fields the employee must confirm before using the ERP.
     *
     * @var list<string>
     */
    public const REQUIRED_FIELDS = [
        'first_name',
        'surname',
        'date_of_birth',
        'gender',
        'primary_email',
        'phone_number',
        'marital_status',
        'physical_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
    ];

    /** @var array<string, string> */
    public const FIELD_LABELS = [
        'first_name' => 'First name',
        'surname' => 'Surname',
        'date_of_birth' => 'Date of birth',
        'gender' => 'Gender at birth',
        'primary_email' => 'Personal email',
        'organisation_email' => 'Organisational / Secondary email',
        'preferred_erp_email' => 'Preferred ERP email',
        'phone_number' => 'Phone number',
        'marital_status' => 'Marital status',
        'physical_address' => 'Physical address',
        'emergency_contact_name' => 'Emergency contact name',
        'emergency_contact_phone' => 'Emergency contact phone',
        'emergency_contact_relationship' => 'Emergency contact relationship',
        'middle_name' => 'Middle name',
        'alt_phone_number' => 'Alternative phone',
        'postal_address' => 'Postal address',
        'postal_code' => 'Postal code',
        'home_county' => 'Home county',
        'photo' => 'Profile photo',
        'qualification' => 'Qualification / certificate',
        'national_id_number' => 'National ID number',
        'kra_pin' => 'KRA PIN',
        'nssf_number' => 'NSSF number',
        'sha_number' => 'SHA number',
        'next_of_kin' => 'Next of kin details',
    ];

    /** @var array<string, string> */
    public const DOCUMENT_TYPE_LABELS = [
        'cv' => 'CV',
        'academic_certificate' => 'Academic certificate',
        'professional_license' => 'Professional license',
        'kra_pin' => 'KRA PIN document',
        'nssf' => 'NSSF document',
        'sha' => 'SHA document',
        'national_id' => 'National ID',
        'good_conduct' => 'Certificate of good conduct',
        'passport_photo' => 'Passport photo',
        'bank_confirmation' => 'Bank confirmation',
        'training_certification' => 'Training certification',
        'other' => 'Other document',
    ];

    /**
     * Fields HR / ICT can ask employees to update in My Employee Portal.
     *
     * @return array<string, string>
     */
    public static function requestableFieldLabels(): array
    {
        $labels = self::FIELD_LABELS;

        foreach (self::DOCUMENT_TYPE_LABELS as $type => $label) {
            $labels[self::documentFieldKey($type)] = 'Upload: '.$label;
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    public static function requestableFieldKeys(): array
    {
        return array_keys(self::requestableFieldLabels());
    }

    public static function documentFieldKey(string $type): string
    {
        return 'document_'.$type;
    }

    public static function documentTypeFromFieldKey(string $key): ?string
    {
        if (! str_starts_with($key, 'document_')) {
            return null;
        }

        $type = substr($key, strlen('document_'));

        return array_key_exists($type, self::DOCUMENT_TYPE_LABELS) ? $type : null;
    }

    /**
     * @param  list<string>|null  $requestedFields
     * @return list<string>
     */
    public static function requestedDocumentTypes(?array $requestedFields): array
    {
        $types = [];
        foreach ($requestedFields ?? [] as $field) {
            $type = self::documentTypeFromFieldKey((string) $field);
            if ($type) {
                $types[] = $type;
            }
        }

        return array_values(array_unique($types));
    }

    public static function isDocumentField(string $key): bool
    {
        return self::documentTypeFromFieldKey($key) !== null;
    }

    public function __construct(
        protected EmployeePortalService $employeePortal,
    ) {}

    public function staffForUser(User $user): ?Staff
    {
        if ($user->isEnrolledStudent()) {
            return null;
        }

        return $this->employeePortal->staffForUser($user);
    }

    public function mustCompleteProfile(User $user): bool
    {
        if (app(RBACService::class)->isPlatformAdministrator($user)) {
            return false;
        }

        $staff = $this->staffForUser($user);

        return $staff !== null && ! $this->isComplete($staff);
    }

    public function isComplete(Staff $staff): bool
    {
        return $this->missingFields($staff) === [];
    }

    /**
     * @return list<string> field keys
     */
    public function missingFields(Staff $staff): array
    {
        $missing = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            $value = $staff->{$field} ?? null;
            if (! $this->filled($field, $value)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    public function missingLabels(Staff $staff): array
    {
        return array_map(
            fn (string $field) => self::FIELD_LABELS[$field] ?? $field,
            $this->missingFields($staff)
        );
    }

    private function filled(string $field, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if ($value instanceof \DateTimeInterface) {
            // Provisional invite DOB placeholder
            if ($field === 'date_of_birth' && $value->format('Y-m-d') === '1990-01-01') {
                return false;
            }

            return true;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return false;
            }

            $normalized = strtolower($trimmed);

            // Provisional invite placeholders are not real completed data.
            if (in_array($normalized, [
                '0700000000',
                '0000000000',
                'tbd',
                'n/a',
                'pending',
                'invitee',
                'unspecified',
                'pending assignment',
            ], true)) {
                return false;
            }

            if ($field === 'first_name' && $normalized === 'pending') {
                return false;
            }

            if ($field === 'surname' && $normalized === 'invitee') {
                return false;
            }

            if ($field === 'gender' && $normalized === 'unspecified') {
                return false;
            }
        }

        return true;
    }
}
