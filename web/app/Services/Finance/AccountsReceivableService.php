<?php

namespace App\Services\Finance;

use App\Mail\InvoicePaymentReminderMail;
use App\Models\Invoice;
use App\Support\ModuleMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccountsReceivableService
{
    public const BUCKET_KEYS = ['current', 'days_14', 'days_30', 'days_60', 'days_90_plus'];

    public function __construct(
        protected FinanceSmsService $sms,
    ) {}

    /**
     * @return Builder<Invoice>
     */
    public function openInvoicesQuery(): Builder
    {
        return Invoice::query()
            ->with(['student.applicant', 'student.program'])
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->where('balance', '>', 0);
    }

    public function daysPastDue(Invoice $invoice): int
    {
        if ((float) $invoice->balance <= 0) {
            return 0;
        }

        $anchor = $invoice->due_date ?? $invoice->issue_date;

        if (! $anchor) {
            return 0;
        }

        $due = Carbon::parse($anchor)->startOfDay();

        if ($due->isFuture()) {
            return 0;
        }

        return (int) $due->diffInDays(now()->startOfDay());
    }

    public function bucketForDays(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue >= 90 => 'days_90_plus',
            $daysPastDue >= 60 => 'days_60',
            $daysPastDue >= 30 => 'days_30',
            $daysPastDue >= 14 => 'days_14',
            default => 'current',
        };
    }

    public function bucketLabel(string $bucket): string
    {
        return match ($bucket) {
            'current' => 'Current (0–13 days)',
            'days_14' => '14–29 days',
            'days_30' => '30–59 days',
            'days_60' => '60–89 days',
            'days_90_plus' => '90+ days',
            default => ucfirst($bucket),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function agingReport(): array
    {
        $invoices = $this->openInvoicesQuery()->orderBy('due_date')->get();

        $buckets = collect(self::BUCKET_KEYS)->mapWithKeys(fn (string $key) => [
            $key => ['label' => $this->bucketLabel($key), 'count' => 0, 'total' => 0.0, 'invoices' => collect()],
        ])->all();

        foreach ($invoices as $invoice) {
            $days = $this->daysPastDue($invoice);
            $bucket = $this->bucketForDays($days);
            $buckets[$bucket]['count']++;
            $buckets[$bucket]['total'] = round($buckets[$bucket]['total'] + (float) $invoice->balance, 2);
            $buckets[$bucket]['invoices']->push([
                'invoice' => $invoice,
                'days_past_due' => $days,
            ]);
        }

        $totalOutstanding = round($invoices->sum(fn (Invoice $invoice) => (float) $invoice->balance), 2);

        return [
            'as_at' => now()->toDateString(),
            'total_outstanding' => $totalOutstanding,
            'invoice_count' => $invoices->count(),
            'buckets' => $buckets,
        ];
    }

    public function markOverdueInvoices(): int
    {
        return Invoice::query()
            ->whereIn('status', ['issued', 'partial'])
            ->where('balance', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function sendDueReminders(bool $dryRun = false): array
    {
        $intervalDays = (int) config('finance.ar.reminder_interval_days', 7);
        $beforeDueDays = (int) config('finance.ar.reminder_days_before_due', 3);
        $cutoff = now()->subDays($intervalDays);

        $candidates = $this->openInvoicesQuery()
            ->where(function (Builder $query) use ($beforeDueDays) {
                $query->whereDate('due_date', '<', now()->toDateString())
                    ->orWhereBetween('due_date', [now()->toDateString(), now()->addDays($beforeDueDays)->toDateString()]);
            })
            ->where(function (Builder $query) use ($cutoff) {
                $query->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<=', $cutoff);
            })
            ->get();

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $invoice) {
            if ($dryRun) {
                $sent++;

                continue;
            }

            if ($this->sendReminder($invoice)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return compact('sent', 'skipped', 'failed');
    }

    public function sendReminder(Invoice $invoice): bool
    {
        $invoice->loadMissing(['student.applicant', 'student.user', 'student.program']);

        $email = $invoice->student?->applicant?->email ?? $invoice->student?->user?->email;
        $phone = $invoice->student?->applicant?->phone_number;
        $emailSent = false;
        $smsSent = false;

        if ($email) {
            try {
                ModuleMail::send(ModuleMail::FINANCE, $email, new InvoicePaymentReminderMail($invoice));
                $emailSent = true;
            } catch (Throwable $e) {
                Log::error('Invoice reminder email failed', [
                    'invoice_id' => $invoice->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($phone) {
            $smsSent = $this->sms->send(
                $phone,
                sprintf(
                    'TICH: Invoice %s balance KES %s due %s. Pay via student portal.',
                    $invoice->invoice_number,
                    number_format((float) $invoice->balance, 2),
                    $invoice->due_date?->format('d M Y') ?? 'soon'
                )
            );
        }

        if ($emailSent || $smsSent) {
            $invoice->update([
                'last_reminder_sent_at' => now(),
                'reminder_count' => (int) $invoice->reminder_count + 1,
            ]);

            return true;
        }

        return false;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return list<array<string, mixed>>
     */
    public function invoiceRows(Collection $invoices): array
    {
        return $invoices->map(function (Invoice $invoice) {
            return [
                'invoice' => $invoice,
                'days_past_due' => $this->daysPastDue($invoice),
                'bucket' => $this->bucketForDays($this->daysPastDue($invoice)),
            ];
        })->all();
    }
}
