@extends('layouts.hr')

@section('title', 'Send policy - ' . $policy->title)

@section('hr-content')
    <x-page-toolbar title="Send policy" :meta="$policy->title . ' · HR Policies'">
        <x-slot:actions>
            <a href="{{ route('hr.policies.show', $policy) }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.policies.send.store', $policy) }}" class="tich-form-stack">
            @csrf
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div class="tich-grid--span-2">
                    <label class="tich-label">Policy</label>
                    <input type="text" value="{{ $policy->title }} (v{{ $policy->version }})" disabled class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label class="tich-checkbox">
                        <input type="checkbox" name="send_to_all" value="1" id="send-to-all" onchange="document.getElementById('staff-select').style.display = this.checked ? 'none' : 'block';">
                        Send to all staff
                    </label>
                </div>
                <div id="staff-select" class="tich-grid--span-2">
                    <label for="staff_ids" class="tich-label">Select staff *</label>
                    <select id="staff_ids" name="staff_ids[]" multiple required class="tich-input" style="min-height: 200px;">
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}">
                                {{ $staff->fullName() }} ({{ $staff->employee_number }}) · {{ $staff->job_title ?? 'Staff' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="tich-caption tich-mt-1">Hold Ctrl/Cmd to select multiple staff members.</p>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Send policy</button>
                <a href="{{ route('hr.policies.show', $policy) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
