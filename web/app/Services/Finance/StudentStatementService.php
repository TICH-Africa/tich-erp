<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentStatementService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function buildStatement(Student $student): array
    {
        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->orderBy('issue_date')
            ->get();

        $payments = Payment::query()
            ->where('student_id', $student->id)
            ->orderBy('payment_date')
            ->get();

        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'date' => $invoice->issue_date,
                'sort_at' => strtotime((string) ($invoice->issue_date ?? now())).'-0',
                'type' => 'invoice',
                'reference' => $invoice->invoice_number,
                'description' => $invoice->description ?: ucwords(str_replace('_', ' ', (string) $invoice->invoice_type)),
                'debit' => (float) $invoice->amount,
                'credit' => 0.0,
            ]);
        }

        foreach ($payments as $payment) {
            $entries->push([
                'date' => $payment->payment_date,
                'sort_at' => strtotime((string) ($payment->payment_date ?? now())).'-1',
                'type' => 'payment',
                'reference' => $payment->payment_number,
                'description' => 'Payment · '.ucwords(str_replace('_', ' ', (string) $payment->payment_method)),
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
            ]);
        }

        $running = 0.0;

        return $entries
            ->sortBy('sort_at')
            ->values()
            ->map(function (array $entry) use (&$running) {
                $running = round($running + $entry['debit'] - $entry['credit'], 2);
                $entry['running_balance'] = $running;

                return $entry;
            })
            ->all();
    }

    /**
     * @param  Collection<int, Student>|array<int, Student>  $students
     * @return list<array{student: Student, entries: list<array<string, mixed>>, outstanding: float}>
     */
    public function bulkStatements(Collection|array $students): array
    {
        $collection = $students instanceof Collection ? $students : collect($students);
        $payload = [];

        foreach ($collection as $student) {
            $student->loadMissing(['applicant', 'program']);
            $entries = $this->buildStatement($student);
            $outstanding = (float) Invoice::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['issued', 'partial', 'overdue'])
                ->sum('balance');

            $payload[] = [
                'student' => $student,
                'entries' => $entries,
                'outstanding' => $outstanding,
            ];
        }

        return $payload;
    }
}
