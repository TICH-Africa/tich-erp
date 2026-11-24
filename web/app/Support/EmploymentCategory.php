<?php

namespace App\Support;

class EmploymentCategory
{
    public const CONSULTANCY_LABEL = 'Consultancy/Independent contractor';

    public const WITHHOLDING_PAYMENT_STATEMENT_TITLE = 'Consultancy/Independent contractor payment statement';

    /** @var list<string> */
    private const LEGACY_WITHHOLDING_CODES = ['consultant', 'independent_contractor'];

    public static function normalize(?string $code): ?string
    {
        $code = strtolower(trim((string) $code));
        if ($code === '') {
            return null;
        }

        return match ($code) {
            'consultant', 'independent_contractor' => 'consultancy',
            default => $code,
        };
    }

    public static function label(?string $code): string
    {
        $normalized = self::normalize($code);
        if ($normalized === null) {
            return '-';
        }

        $fromEmployment = config('tich-payroll.employment_categories.'.$normalized);
        if (is_string($fromEmployment) && $fromEmployment !== '') {
            return $fromEmployment;
        }

        $fromContract = config('tich-payroll.contract_types.'.$normalized);
        if (is_string($fromContract) && $fromContract !== '') {
            return $fromContract;
        }

        return ucfirst(str_replace('_', ' ', $normalized));
    }

    public static function contractTypeFromEmploymentCategory(?string $category): ?string
    {
        $category = self::normalize($category);
        if ($category === null) {
            return null;
        }

        $types = config('tich-payroll.contract_types', []);

        return array_key_exists($category, $types) ? $category : null;
    }

    public static function isWithholdingEmployment(?string $code): bool
    {
        $normalized = self::normalize($code);
        if ($normalized === null) {
            return false;
        }

        return in_array(
            $normalized,
            config('tich-payroll.withholding_employment_categories', []),
            true
        );
    }

    /**
     * @return list<string>
     */
    public static function legacyWithholdingCodes(): array
    {
        return self::LEGACY_WITHHOLDING_CODES;
    }
}