<?php

namespace App\Models\Finance;

use App\Models\Student;
use App\Models\Semester;
use App\Models\Finance\FeeStructure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'student_account_id',
        'student_id',
        'semester_id',
        'fee_structure_id',
        'invoice_type',
        'description',
        'amount',
        'amount_paid',
        'balance',
        'issue_date',
        'due_date',
        'status',
        'payment_gateway_ref',
        'is_sent_to_portal',
        'sent_at',
        'waiver_reason',
        'waived_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'is_sent_to_portal' => 'boolean',
        'sent_at' => 'datetime',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'waived_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * The bill's lines. Bills raised before lines were stored stand in with the charges
     * written into their description, so the screen and the printed invoice are never
     * empty and never collapse the breakdown into one block of text.
     *
     * @return Collection<int, InvoiceItem>
     */
    public function lineItems(): Collection
    {
        if ($this->items->isNotEmpty()) {
            return $this->items;
        }

        $charges = collect($this->chargesFromDescription())
            ->map(fn (array $charge) => new InvoiceItem($charge + ['net_amount' => $charge['amount']]));

        if ($charges->isNotEmpty()) {
            return $charges;
        }

        return collect([new InvoiceItem([
            'fee_item' => $this->invoice_type.'_fee',
            'description' => mb_substr($this->flatDescription(), 0, 300),
            'amount' => $this->amount,
            'net_amount' => $this->amount,
        ])]);
    }

    /**
     * Split "Tuition fee: KES 42,560.00; Library: KES 1,500.00" back into rows, but only
     * when those charges really add up to the billed amount.
     *
     * @return list<array{fee_item: string, description: string, amount: float}>
     */
    private function chargesFromDescription(): array
    {
        $text = (string) $this->description;
        if (! str_contains($text, 'KES')) {
            return [];
        }

        $charges = [];
        $total = 0.0;

        preg_match_all('/([^:;\r\n]+):\s*KES\s*([\d,]+(?:\.\d{1,2})?)/', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $label, $figure]) {
            $amount = round((float) str_replace(',', '', $figure), 2);
            $label = trim($label);
            if ($amount <= 0 || $label === '') {
                continue;
            }

            $total = round($total + $amount, 2);
            $charges[] = [
                'fee_item' => trim(strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $label)), '_'),
                'description' => $label,
                'amount' => $amount,
            ];
        }

        return abs($total - round((float) $this->amount, 2)) >= 0.01 ? [] : $charges;
    }

    private function flatDescription(): string
    {
        return trim((string) preg_replace('/\s*[\r\n]+\s*/', ' ', (string) $this->description));
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute(): bool
    {
        return ! in_array($this->status, ['paid', 'waived']) && $this->due_date < now()->toDateString();
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['issued', 'partial', 'overdue']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')->orWhere(function ($q) {
            $q->whereIn('status', ['issued', 'partial'])->where('due_date', '<', now()->toDateString());
        });
    }
}
