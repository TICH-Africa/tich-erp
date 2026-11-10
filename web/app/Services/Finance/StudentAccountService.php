<?php

namespace App\Services\Finance;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentAccount;
use Illuminate\Support\Collection;

class StudentAccountService
{
    public function __construct(
        protected LedgerService $ledger,
    ) {}

    public function ensureAccount(Student $student, ?int $academicYearId = null): StudentAccount
    {
        $yearId = $academicYearId ?? $this->currentAcademicYearId();

        return StudentAccount::query()->firstOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $yearId,
            ],
            [
                'total_chargeable' => 0,
                'total_paid' => 0,
                'outstanding_balance' => 0,
            ]
        );
    }

    public function recalculate(StudentAccount $account): StudentAccount
    {
        [$chargeable, $paid, $credits] = $this->totals($account);

        $outstanding = max(round($chargeable - $paid - $credits, 2), 0);

        $account->update([
            'total_chargeable' => round($chargeable, 2),
            'total_paid' => round($paid, 2),
            'outstanding_balance' => $outstanding,
            // Money taken in over what has been billed. An invoice raised later
            // deducts this before asking the student for cash again.
            'credit_balance' => max(round($paid + $credits - $chargeable, 2), 0),
            'is_cleared' => $outstanding <= 0 && $chargeable > 0,
            'cleared_at' => $outstanding <= 0 && $chargeable > 0 ? now() : null,
            'last_payment_date' => $account->payments()->max('payment_date'),
        ]);

        $this->syncStudentSummary($account);

        return $account->fresh();
    }

    /**
     * The excess payment the student can spend on the next invoice.
     */
    public function creditAvailable(StudentAccount $account): float
    {
        [$chargeable, $paid, $credits] = $this->totals($account);

        return max(round($paid + $credits - $chargeable, 2), 0);
    }

    /**
     * What the student still owes, measured on the account rather than on one
     * invoice: every open bill less the cash already in and the HELB, scholarship
     * and sponsor cover. A payment above this figure is what carries forward.
     */
    public function amountOwed(StudentAccount $account): float
    {
        [$chargeable, $paid, $credits] = $this->totals($account);

        return max(round($chargeable - $paid - $credits, 2), 0);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function totals(StudentAccount $account): array
    {
        $chargeable = (float) $account->invoices()
            ->whereNotIn('status', ['waived'])
            ->sum('amount');

        $paid = (float) $account->payments()->sum('amount');
        $credits = (float) $account->scholarship_amount
            + (float) $account->helb_amount
            + (float) $account->sponsor_amount
            + (float) $account->work_study_credit;

        return [$chargeable, $paid, $credits];
    }

    /**
     * @return Collection<int, StudentAccount>
     */
    public function listAccounts(?string $search = null): Collection
    {
        $query = StudentAccount::query()
            ->with(['student.applicant', 'student.program', 'academicYear'])
            ->orderByDesc('updated_at');

        if ($search) {
            $query->whereHas('student', function ($studentQuery) use ($search) {
                $studentQuery->where('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', function ($applicantQuery) use ($search) {
                        $applicantQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get();
    }

    private function syncStudentSummary(StudentAccount $account): void
    {
        $student = $account->student;
        if (! $student) {
            return;
        }

        $totalOutstanding = (float) StudentAccount::query()
            ->where('student_id', $student->id)
            ->sum('outstanding_balance');

        $student->update([
            'overall_balance' => $totalOutstanding,
            'fee_clearance_status' => $totalOutstanding <= 0 ? 'cleared' : 'pending',
        ]);
    }

    private function currentAcademicYearId(): int
    {
        $existing = AcademicYear::query()->where('is_current', 1)->value('id')
            ?? AcademicYear::query()->orderByDesc('start_date')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        // Local/dev databases may have students before any academic year is configured.
        $start = now()->month >= 9
            ? now()->copy()->month(9)->startOfMonth()
            : now()->copy()->subYear()->month(9)->startOfMonth();
        $end = $start->copy()->addYear()->subDay();
        $label = $start->format('Y').'/'.$end->format('Y');

        $year = AcademicYear::query()->firstOrCreate(
            ['year_label' => $label],
            [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'is_current' => 1,
            ]
        );

        if (! $year->is_current) {
            AcademicYear::query()->whereKey($year->id)->update(['is_current' => 1]);
        }

        return (int) $year->id;
    }
}
