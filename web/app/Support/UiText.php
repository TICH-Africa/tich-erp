<?php

namespace App\Support;

class UiText
{
    public static function normalizeDash(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        return str_replace(["\u{2014}", "\u{2013}"], '-', $text);
    }

    public static function termLabel(?string $label, ?int $semesterNumber = null): string
    {
        return \App\Models\Semester::normalizeLabel($label, $semesterNumber);
    }

    public static function unitLabel(?string $unitCode, ?string $unitName): string
    {
        $code = trim((string) $unitCode);
        $name = trim((string) $unitName);

        if ($code === '' && $name === '') {
            return '';
        }

        if ($code === '') {
            return self::normalizeDash($name) ?? '';
        }

        if ($name === '') {
            return self::normalizeDash($code) ?? '';
        }

        return self::normalizeDash($code.' - '.$name) ?? '';
    }
}
