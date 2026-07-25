@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <header class="tich-dept-header">
        <h1 class="tich-h1 tich-dept-header__title">Attendance verification ledger</h1>
        <p class="tich-text">Review submitted attendance sessions with signed sheet photos and matched digital rosters.</p>
    </header>

    <form method="GET" class="tich-card tich-mt-6" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        @if (! empty($learningDepartments) && empty($learningDepartment))
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Learning department</label>
                <select name="learning_department" class="tich-input">
                    @foreach ($learningDepartments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('learning_department') == $dept->id)>{{ $dept->dept_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Verification status</label>
            <select name="status" class="tich-input">
                <option value="">All submitted</option>
                <option value="submitted" @selected($selectedStatus === 'submitted')>Awaiting HOD</option>
                <option value="hod_verified" @selected($selectedStatus === 'hod_verified')>Awaiting Registrar</option>
                <option value="registrar_verified" @selected($selectedStatus === 'registrar_verified')>Fully verified</option>
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-mt-8" style="overflow-x:auto; padding:0;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Tracking ID</th>
                    <th>Unit</th>
                    <th>Date</th>
                    <th>Tutor</th>
                    <th>Status</th>
                    <th>Signed sheet</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $session)
                    <tr>
                        <td>{{ $session->session_number }}</td>
                        <td>{{ $session->unit_code }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($session->session_date)->format('d M Y') }}</td>
                        <td>{{ trim($session->tutor_first_name.' '.$session->tutor_surname) }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($session->verification_status)) }}</td>
                        <td>
                            @if ($session->signed_sheet_image_path)
                                <a href="{{ asset('storage/'.$session->signed_sheet_image_path) }}" target="_blank" class="tich-link">View photo</a>
                            @else
                                —
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            @if ($canVerifyHod && $session->verification_status === 'submitted')
                                <form method="POST" action="{{ route('departments.academics.attendance-ledger.verify-hod', array_merge($hub, ['session' => $session->id])) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link">Verify (HOD)</button>
                                </form>
                            @endif
                            @if ($canVerifyRegistrar && in_array($session->verification_status, ['submitted', 'hod_verified'], true))
                                <form method="POST" action="{{ route('departments.academics.attendance-ledger.verify-registrar', array_merge($hub, ['session' => $session->id])) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link">Verify (Registrar)</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="tich-text">No submitted attendance sessions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
