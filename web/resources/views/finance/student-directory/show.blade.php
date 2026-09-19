@extends('layouts.finance')

@section('title', 'Student Financial Profile')

@section('finance-content')
    <a href="{{ route('finance.students.index') }}" class="tich-link">&larr; Back to directory</a>

    <x-page-toolbar :title="$student->registration_number" meta="{{ $student->program?->program_name ?? '-' }} · {{ ucfirst($student->enrollment_status) }}" />

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Total Records</p>
            <p class="tich-stat__value">{{ $summary['total_records'] }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Total Chargeable</p>
            <p class="tich-stat__value">KES {{ number_format($summary['total_chargeable'], 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Total Paid</p>
            <p class="tich-stat__value">KES {{ number_format($summary['total_paid'], 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Outstanding</p>
            <p class="tich-stat__value">KES {{ number_format($summary['total_outstanding'], 2) }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card">
            <h3 class="tich-h4">Academic Programme</h3>
            <dl class="tich-dl tich-mt-3">
                <dt class="tich-caption">Programme</dt>
                <dd>{{ $student->program?->program_name ?? '-' }}</dd>
                <dt class="tich-caption">Campus</dt>
                <dd>{{ $student->campus?->campus_name ?? '-' }}</dd>
                <dt class="tich-caption">Duration</dt>
                <dd>{{ $student->program?->duration_months ?? '-' }} months</dd>
                <dt class="tich-caption">Program Type</dt>
                <dd>{{ $student->entry_pathway ?? '-' }}</dd>
            </dl>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Financial Actions</h3>
            <div class="tich-mt-3">
                <form method="POST" action="{{ route('finance.students.add-past-record', $student->id) }}">
                    @csrf
                    <div class="tich-grid tich-grid--2 tich-mt-3">
                        <div>
                            <label class="tich-label">Chargeable (KES)</label>
                            <input type="number" name="total_chargeable" required min="0" step="0.01" class="tich-input" />
                        </div>
                        <div>
                            <label class="tich-label">Paid (KES)</label>
                            <input type="number" name="total_paid" min="0" step="0.01" class="tich-input" />
                        </div>
                        <div>
                            <label class="tich-label">Payment Method</label>
                            <select name="payment_method" class="tich-input">
                                <option value="">-- Select --</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                        <div>
                            <label class="tich-label">Reference</label>
                            <input type="text" name="payment_reference" maxlength="100" class="tich-input" />
                        </div>
                        <div>
                            <label class="tich-label">Payment Date</label>
                            <input type="date" name="payment_date" class="tich-input" style="color: #000000; font-weight: 600; min-height: 40px;" />
                        </div>
                        <div>
                            <label class="tich-label">Academic Year</label>
                            <select name="academic_year_id" class="tich-input">
                                <option value="">-- Select --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->year_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="tich-label">Notes</label>
                            <input type="text" name="notes" maxlength="255" class="tich-input" />
                        </div>
                    </div>
                    <div class="tich-mt-4">
                        <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">Save Record</button>
                    </div>
                </form>
            </div>
        </article>
    </div>

    <div class="tich-card tich-mt-6">
        <h3 class="tich-h3">Past Financial Records</h3>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Chargeable</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($financialHistory as $record)
                        <tr>
                            <td>{{ $record->payment_date ?? '-' }}</td>
                            <td>KES {{ number_format((float) $record->total_chargeable, 2) }}</td>
                            <td>KES {{ number_format((float) $record->total_paid, 2) }}</td>
                            <td>KES {{ number_format((float) $record->outstanding_balance, 2) }}</td>
                            <td>{{ $record->payment_method ?? '-' }}</td>
                            <td>{{ $record->payment_reference ?? '-' }}</td>
                            <td>{{ $record->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-caption">No financial records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection
