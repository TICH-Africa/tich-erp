<?php

namespace App\Imports;

use App\Models\Student;
use App\Support\SpreadsheetTabularReader;

/**
 * Reads a sheet of past-year graduates and works out what each line means for the register.
 *
 * It only ever matches lines to students the school already has - an old cohort that never got an
 * ERP record cannot be given a graduation date, a programme or a transcript here, so those lines are
 * reported for Admissions to add one by one first.
 */
class AlumniGraduatesImport
{
    private const HEADER_ALIASES = [
        'registration_number' => ['registration_number', 'reg_no', 'reg_number', 'registration_no', 'admission_number', 'adm_no', 'index_number', 'student_number', 'student_no'],
        'first_name' => ['first_name', 'firstname', 'given_name', 'forename'],
        'middle_name' => ['middle_name', 'middlename', 'second_name'],
        'surname' => ['surname', 'last_name', 'lastname', 'family_name'],
        'full_name' => ['full_name', 'name', 'student_name', 'complete_name'],
        'graduation_year' => ['graduation_year', 'grad_year', 'year_graduated', 'graduated_year', 'convocation_year', 'year'],
        'graduation_date' => ['graduation_date', 'grad_date', 'date_graduated', 'convocation_date'],
        'graduation_award' => ['graduation_award', 'award', 'class', 'honours', 'class_of_honours', 'award_class', 'degree'],
    ];

    /**
     * @return array{rows: list<array<string, mixed>>, counts: array<string, int>, errors: list<string>}
     */
    public function preview(string $path, ?string $extension = null): array
    {
        $extension = strtolower((string) ($extension ?: pathinfo($path, PATHINFO_EXTENSION)));

        $rows = array_values(array_filter(
            (new SpreadsheetTabularReader)->rows($path, $extension),
            static fn ($row) => is_array($row) && $row !== []
        ));

        if ($rows === []) {
            return $this->result([], ['file' => 'The uploaded file is empty.']);
        }

        $headerIndex = $this->detectHeaderRow($rows);

        if ($headerIndex === null) {
            return $this->result([], ['file' => 'No header row found. Row 1 needs an admission number column and a name column.']);
        }

        $columns = $this->mapColumns($rows[$headerIndex]);

        if (! isset($columns['registration_number']) && ! isset($columns['full_name']) && ! (isset($columns['first_name']) && isset($columns['surname']))) {
            return $this->result([], ['file' => 'The file needs an admission number column, or a full name column, so each line can be matched to a student.']);
        }

        $marks = [];
        $lookup = $this->loadCandidates($rows, $columns, $headerIndex);
        $line = $headerIndex + 2;

        foreach (array_slice($rows, $headerIndex + 1) as $raw) {
            $values = $this->readValues($raw, $columns);
            $number = $this->upper((string) ($values['registration_number'] ?? ''));
            $asWritten = trim(implode(' ', array_filter([
                $values['first_name'] ?? '',
                $values['middle_name'] ?? '',
                $values['surname'] ?? '',
                $values['full_name'] ?? '',
            ])));
            $name = $this->upper($asWritten);

            if ($number === '' && $name === '') {
                $line++;
                continue;
            }

            $student = $number !== ''
                ? ($lookup['numbers'][$number] ?? null)
                : $this->byName($lookup, $name);

            $year = $this->year($values['graduation_year'] ?? null, $values['graduation_date'] ?? null);
            $date = $this->date($values['graduation_date'] ?? null);
            $award = trim((string) ($values['graduation_award'] ?? ''));

            $found = $student?->displayName() ?? $asWritten;

            $marks[] = match (true) {
                $student === null && $number !== '' => $this->row($line, $number, $found, $year, $date, $award, null, 'not_found', 'No student carries that admission number.'),
                $student === null => $this->row($line, $number, $found, $year, $date, $award, null, 'not_found', 'No single student matches that name - add the admission number.'),
                $year === null && $date === null => $this->row($line, $number, $found, $year, $date, $award, $student->id, 'invalid', 'Needs a graduation year or date.'),
                in_array($student->enrollment_status, ['graduated', 'alumni'], true) => $this->row($line, $number, $found, $year, $date, $award, $student->id, 'already', 'Already on the alumni register.'),
                default => $this->row($line, $number, $found, $year, $date, $award, $student->id, 'mark', $student->registration_number),
            };

            $line++;
        }

        return $this->result($marks);
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @param  array<string, int>  $columns
     * @return array{numbers: array<string, Student>, names: array<string, list<Student>>}
     */
    private function loadCandidates(array $rows, array $columns, int $headerIndex): array
    {
        $numbers = [];
        $names = [];

        foreach (array_slice($rows, $headerIndex + 1) as $raw) {
            $values = $this->readValues($raw, $columns);
            $number = $this->upper((string) ($values['registration_number'] ?? ''));
            $name = $this->upper(trim(implode(' ', array_filter([
                $values['first_name'] ?? '',
                $values['middle_name'] ?? '',
                $values['surname'] ?? '',
                $values['full_name'] ?? '',
            ]))));

            if ($number !== '') {
                $numbers[] = $number;
            }
            if ($name !== '') {
                $names[] = $name;
            }
        }

        $numbers = array_values(array_unique($numbers));
        $names = array_values(array_unique($names));

        // A sheet rarely carries a middle name, so surname is the cheapest safe narrowing: the full
        // name is then compared in PHP, where a dropped middle name makes a line ambiguous rather
        // than a silent guess at the wrong person.
        $surnames = array_values(array_unique(array_filter(array_map(
            static fn (string $name) => trim(substr($name, strrpos($name, ' ')) ?: $name),
            $names
        ))));

        $found = Student::query()
            ->when(
                $numbers !== [] || $surnames !== [],
                function ($q) use ($numbers, $surnames) {
                    $q->where(function ($inner) use ($numbers, $surnames) {
                        if ($numbers !== []) {
                            $inner->orWhereIn('registration_number', $numbers);
                        }
                        if ($surnames !== []) {
                            $inner->orWhereIn(\Illuminate\Support\Facades\DB::raw('UPPER(surname)'), $surnames);
                        }
                    });
                }
            )
            ->get();

        $byNumber = $found->keyBy(fn (Student $student) => $this->upper((string) $student->registration_number));

        // Names come from the school's own spelling of first + middle + surname, so a sheet that
        // drops a middle name still has to be ambiguous rather than silently guess.
        $byName = [];
        foreach ($found as $student) {
            $key = $this->upper(trim(collect([$student->first_name, $student->middle_name, $student->surname])->filter()->implode(' ')));
            if ($key !== '') {
                $byName[$key][] = $student;
            }
        }

        return ['numbers' => $byNumber, 'names' => $byName];
    }

    /**
     * @param  array<string, list<Student>>  $lookup
     */
    private function byName(array $lookup, string $name): ?Student
    {
        $candidates = $lookup['names'][$name] ?? [];

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    /**
     * @param  array<string, int>  $columns
     * @return array<string, string>
     */
    private function readValues(array $raw, array $columns): array
    {
        $values = [];

        foreach ($columns as $field => $index) {
            $values[$field] = trim((string) ($raw[$index] ?? ''));
        }

        return $values;
    }

    /**
     * @return array<string, int>|null
     */
    private function detectHeaderRow(array $rows): ?int
    {
        foreach (array_slice($rows, 0, 10) as $index => $row) {
            if ($this->mapColumns($row) !== []) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    private function mapColumns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $cell) {
            $needle = $this->normalise((string) $cell);

            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if (isset($columns[$field])) {
                    continue;
                }

                if (in_array($needle, $aliases, true)) {
                    $columns[$field] = $index;
                    break;
                }
            }
        }

        return $columns;
    }

    private function normalise(string $value): string
    {
        $value = str_replace([' ', '﻿'], ' ', $value);

        return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $value) ?? '', '_'));
    }

    private function upper(string $value): string
    {
        return strtoupper(trim($value));
    }

    private function year(mixed $year, mixed $date): ?int
    {
        $digits = preg_replace('/\D/', '', (string) $year) ?: '';

        if (strlen($digits) >= 4) {
            return (int) substr($digits, -4);
        }

        return $this->date($date) ? (int) substr($this->date($date), 0, 4) : null;
    }

    /**
     * Accepts 2019-05-30, 30/05/2019 and a PhpSpreadsheet day serial.
     */
    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $match) === 1) {
            return $match[0];
        }

        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $value, $match) === 1) {
            return $match[3].'-'.str_pad($match[2], 2, '0', STR_PAD_LEFT).'-'.str_pad($match[1], 2, '0', STR_PAD_LEFT);
        }

        if (ctype_digit($value) && (int) $value > 20000 && (int) $value < 80000) {
            $stamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((int) $value);

            return date('Y-m-d', (int) $stamp);
        }

        $time = strtotime($value);

        return $time === false ? null : date('Y-m-d', $time);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(int $line, string $number, string $name, ?int $year, ?string $date, string $award, ?int $studentId, string $status, string $note): array
    {
        return [
            'line' => $line,
            'registration_number' => $number,
            'name' => $name,
            'graduation_year' => $year,
            'graduation_date' => $date,
            'graduation_award' => $award !== '' ? $award : null,
            'student_id' => $studentId,
            'status' => $status,
            'note' => $note,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, counts: array<string, int>, errors: list<string>}
     */
    private function result(array $rows, array $errors = []): array
    {
        $counts = array_fill_keys(['mark', 'already', 'not_found', 'invalid'], 0);

        foreach ($rows as $row) {
            $counts[$row['status']] = ($counts[$row['status']] ?? 0) + 1;
        }

        return ['rows' => $rows, 'counts' => $counts, 'errors' => $errors];
    }
}
