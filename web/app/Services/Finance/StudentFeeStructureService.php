<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Services\PrintDocumentService;
use Illuminate\Support\Str;

/**
 * Builds the student's own copy of the fee schedule: what the programme charges, what has
 * been billed, what is still owed, and how to send the money by paybill. Portal and mobile
 * print the same document.
 */
class StudentFeeStructureService
{
    public function __construct(
        protected PrintDocumentService $printDocuments,
        protected MpesaC2bService $c2b,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function documentData(Student $student): array
    {
        $student->loadMissing(['applicant', 'program.department']);
        $account = $this->currentAccount($student);

        return [
            'student' => $student,
            'feeStructure' => $this->structureFor($student, $account),
            'account' => $account,
            'openInvoices' => Invoice::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['issued', 'partial', 'overdue'])
                ->where('balance', '>', 0)
                ->orderBy('issue_date')
                ->get(),
            'paybill' => (string) ($this->c2b->paybill() ?? ''),
            'generatedAt' => now(),
        ];
    }

    public function downloadPdf(Student $student)
    {
        return $this->printDocuments->downloadPdf(
            'portal.fee-structure.pdf',
            $this->documentData($student),
            $this->filename($student),
        );
    }

    public function filename(Student $student): string
    {
        return 'fees-structure-'.Str::slug($student->registration_number).'.pdf';
    }

    private function structureFor(Student $student, ?StudentAccount $account): ?FeeStructure
    {
        $yearId = $account?->academic_year_id;

        $base = FeeStructure::query()
            ->where('program_id', $student->program_id)
            ->where('is_active', 1)
            ->where('is_approved', 1);

        $forYear = $yearId ? (clone $base)->where('academic_year_id', $yearId)->first() : null;

        return $forYear ?? $base->orderByDesc('effective_from')->first();
    }

    /**
     * The account of the year the student is actually in, so the figures printed are the
     * current ones rather than whichever row was inserted last.
     */
    private function currentAccount(Student $student): ?StudentAccount
    {
        return StudentAccount::query()
            ->join('academic_years', 'academic_years.id', '=', 'student_accounts.academic_year_id')
            ->where('student_accounts.student_id', $student->id)
            ->orderByDesc('academic_years.start_date')
            ->select('student_accounts.*')
            ->first();
    }
}
