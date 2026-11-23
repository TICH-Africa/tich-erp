@extends('layouts.hr')

@section('title', 'Send offboarding questionnaire')

@section('hr-content')
    <x-page-toolbar title="Send questionnaire" meta="Use when a contract is ending and renewal is not expected">
        <x-slot:actions>
            <a href="{{ route('hr.offboarding.questionnaires.invitations.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('send')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-mb-6" style="padding: 1.25rem;">
        <h2 class="tich-h4">Contracts expiring soon</h2>
        <p class="tich-caption">Select a row to pre-fill staff and link the contract end date.</p>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Type</th>
                        <th>Ends</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expiringContracts as $contract)
                        <tr>
                            <td>{{ $contract->staff?->fullName() }}</td>
                            <td class="tich-caption">{{ $categoryLabels[$contract->staff?->employment_category] ?? $contract->contract_type }}</td>
                            <td>{{ $contract->end_date?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('hr.offboarding.questionnaires.invitations.create', ['staff_id' => $contract->staff_id, 'staff_contract_id' => $contract->id]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Use</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="tich-caption">No contracts ending in the next 60 days.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('hr.offboarding.questionnaires.invitations.store') }}" class="uf-form tich-card" style="padding: 1.25rem;">
        @csrf
        <input type="hidden" name="staff_contract_id" value="{{ old('staff_contract_id', request('staff_contract_id')) }}">

        <div class="uf-field">
            <label for="staff_id">Employee <span class="uf-req">*</span></label>
            <select name="staff_id" id="staff_id" class="tich-input" required>
                <option value="">Select staff</option>
                @foreach ($staffList as $member)
                    @php
                        $category = $categoryLabels[$member->employment_category] ?? \App\Support\EmploymentCategory::label($member->employment_category);
                        $email = $member->organisation_email ?: $member->primary_email;
                        $contractEnds = $member->contracts->first()?->end_date ?? $member->contract_end_date;
                        $endsLabel = $contractEnds?->format('d M Y') ?? '-';
                    @endphp
                    <option value="{{ $member->id }}" @selected(old('staff_id', $preselectedStaffId) == $member->id)>
                        {{ $member->fullName() }} · {{ $category }} · {{ $email ?: '-' }} · {{ $endsLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="uf-field">
            <label for="template_id">Template (optional)</label>
            <select name="template_id" id="template_id" class="tich-input">
                <option value="">Auto — match employee type</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected(old('template_id') == $template->id)>
                        {{ $template->title }}
                    </option>
                @endforeach
            </select>
            <p class="uf-hint">If left blank, the active template assigned to that employee’s category is used.</p>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Send to employee</button>
    </form>
@endsection