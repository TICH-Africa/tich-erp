@extends('layouts.finance')

@section('title', 'Milestone Details')

@section('finance-content')
    <x-page-toolbar :title="'Milestone ' . ucfirst(str_replace('_', ' ', $milestone->milestone_type))" :meta="'#'.$milestone->id">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.milestones.index') }}" class="tich-btn tich-btn-ghost">Back to Milestones</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <tbody>
                    <tr>
                        <th>Student</th>
                        <td>
                            <strong>{{ optional($milestone->student)->fullName() ?? 'N/A' }}</strong>
                            <p class="tich-caption">{{ optional($milestone->student)->registration_number ?? 'N/A' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Milestone Type</th>
                        <td>{{ ucfirst(str_replace('_', ' ', $milestone->milestone_type)) }}</td>
                    </tr>
                    <tr>
                        <th>Percentage</th>
                        <td>{{ $milestone->percentage }}%</td>
                    </tr>
                    <tr>
                        <th>Target Amount</th>
                        <td>KES {{ number_format($milestone->milestone_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Paid Amount</th>
                        <td>KES {{ number_format($milestone->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="tich-badge tich-badge--{{ match($milestone->status) {
                                'pending' => 'secondary',
                                'partial' => 'warning',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                default => 'secondary',
                            } }}">
                                {{ ucfirst($milestone->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Due Date</th>
                        <td>{{ $milestone->due_date?->format('d M Y') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Paid At</th>
                        <td>{{ $milestone->paid_at?->format('d M Y H:i') ?? 'N/A' }}</td>
                    </tr>
                    @if(optional($milestone->invoice)->invoice_number)
                    <tr>
                        <th>Invoice</th>
                        <td>{{ $milestone->invoice->invoice_number }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection
