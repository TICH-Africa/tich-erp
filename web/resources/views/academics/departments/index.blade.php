@extends('layouts.academics')

@section('academics-content')
    <div class="tich-section__intro" style="text-align: left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Academic departments</h1>
        <p class="tich-text">Initialize learning departments, set curriculum profiles, and complete CEO sign-off.</p>
    </div>

    @if ($canInitialize)
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Initialize department</h2>
            <form method="POST" action="{{ route('academics.departments.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-grid tich-grid--3" style="gap: 1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">Code</label>
                        <input type="text" name="dept_code" class="tich-input" value="{{ old('dept_code') }}" required maxlength="20">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Name</label>
                        <input type="text" name="dept_name" class="tich-input" value="{{ old('dept_name') }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Curriculum profile</label>
                        <select name="curriculum_profile" class="tich-input" required>
                            @foreach ($profiles as $key => $label)
                                <option value="{{ $key }}" @selected(old('curriculum_profile') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="tich-form-group tich-mt-4">
                    <label class="tich-label">Campus (optional)</label>
                    <select name="campus_id" class="tich-input">
                        <option value="">—</option>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}" @selected(old('campus_id') == $campus->id)>{{ $campus->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Initialize department</button>
            </form>
        </article>
    @endif

    <div class="tich-card tich-mt-8" style="overflow-x: auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Profile</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td>{{ $department->dept_code }}</td>
                        <td>{{ $department->dept_name }}</td>
                        <td>{{ $profiles[$department->curriculum_profile ?? 'standard'] ?? ucfirst($department->curriculum_profile ?? 'standard') }}</td>
                        <td>
                            @if (($department->approval_status ?? 'active') === 'pending_ceo')
                                <span class="tich-caption">Pending CEO</span>
                            @elseif ($department->is_active)
                                <span class="tich-caption">Active</span>
                            @else
                                <span class="tich-caption">Inactive</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            @if ($canApproveCeo && ($department->approval_status ?? '') === 'pending_ceo')
                                <form method="POST" action="{{ route('academics.departments.approve-ceo', $department) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-btn tich-btn-secondary">CEO approve</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('academics.departments.update-profile', $department) }}" style="display:inline-flex; gap:0.5rem; align-items:center;">
                                @csrf
                                @method('PUT')
                                <select name="curriculum_profile" class="tich-input" style="width:auto;">
                                    @foreach ($profiles as $key => $label)
                                        <option value="{{ $key }}" @selected(($department->curriculum_profile ?? 'standard') === $key)>{{ $key }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="tich-link">Update profile</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center;" class="tich-text">No learning departments in your scope.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
