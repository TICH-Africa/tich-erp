<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceBudget extends Model
{
    protected $table = 'finance_budgets';

    protected $fillable = [
        'budget_code',
        'budget_name',
        'budget_type',
        'department_id',
        'fiscal_year',
        'period_start',
        'period_end',
        'allocated_amount',
        'spent_amount',
        'committed_amount',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function cycles()
    {
        return $this->hasMany(FinanceBudgetCycle::class, 'budget_id');
    }

    /**
     * Line items embedded in notes by the finance budgeting create form.
     *
     * @return list<array{item: string, quantity: float, description: string, unit_price: float, unit_of_measure: ?string, total: float}>
     */
    public function lineItems(): array
    {
        $notes = (string) ($this->notes ?? '');
        if ($notes === '' || ! str_contains($notes, 'Line items:')) {
            return [];
        }

        if (! preg_match('/Line items:\s*(\[[\s\S]*\])\s*$/u', $notes, $matches)) {
            return [];
        }

        $decoded = json_decode($matches[1], true);
        if (! is_array($decoded)) {
            return [];
        }

        $lines = [];
        foreach ($decoded as $line) {
            if (! is_array($line)) {
                continue;
            }

            $item = trim((string) ($line['item'] ?? ''));
            if ($item === '') {
                continue;
            }

            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $total = isset($line['total']) ? (float) $line['total'] : round($quantity * $unitPrice, 2);

            $lines[] = [
                'item' => $item,
                'quantity' => $quantity > 0 ? $quantity : 1.0,
                'description' => trim((string) ($line['description'] ?? '')),
                'unit_price' => $unitPrice,
                'unit_of_measure' => ($line['unit_of_measure'] ?? null) !== null && trim((string) $line['unit_of_measure']) !== ''
                    ? trim((string) $line['unit_of_measure'])
                    : null,
                'total' => $total,
            ];
        }

        return $lines;
    }

    public function availableAmount(): float
    {
        return max(
            round((float) $this->allocated_amount - (float) $this->spent_amount - (float) $this->committed_amount, 2),
            0
        );
    }
}
