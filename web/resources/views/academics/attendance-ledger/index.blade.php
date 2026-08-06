@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => ($learningDepartment ?? null)?->id ?? request()->integer('learning_department') ?: null,
        ]);
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <x-page-toolbar title="Attendance verification ledger" meta="Submitted sessions with signed sheets and rosters">
        <x-slot:filters>
            <form method="GET" class="tich-page-toolbar__filters-form">
                @if (! empty($learningDepartments) && empty($learningDepartment))
                    <select name="learning_department" class="tich-input tich-input--compact" onchange="this.form.submit()">
                        @foreach ($learningDepartments as $dept)
                            <option value="{{ $dept->id }}" @selected(request('learning_department') == $dept->id)>{{ $dept->dept_name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="status" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    <option value="">All submitted</option>
                    <option value="submitted" @selected($selectedStatus === 'submitted')>Awaiting HOD</option>
                    <option value="hod_verified" @selected($selectedStatus === 'hod_verified')>Awaiting Registrar</option>
                    <option value="registrar_verified" @selected($selectedStatus === 'registrar_verified')>Fully verified</option>
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Tracking ID</th>
                    <th>Unit</th>
                    <th>Date</th>
                    <th>Tutor</th>
                    <th>Status</th>
                    <th>Roster verified</th>
                    <th>Exam eligibility</th>
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
                        <td>{{ $session->roster_verified_at ? \Illuminate\Support\Carbon::parse($session->roster_verified_at)->format('d M Y H:i') : '-' }}</td>
                        <td>
                            @if ($session->exam_eligibility_checked_at)
                                Checked {{ \Illuminate\Support\Carbon::parse($session->exam_eligibility_checked_at)->format('d M Y H:i') }}
                            @else
                                <form method="POST" action="{{ route('departments.academics.attendance-ledger.exam-eligibility', array_merge($hub, ['session' => $session->id])) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link">Check eligibility</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            @if ($session->signed_sheet_image_path)
                                <a href="{{ asset('storage/'.$session->signed_sheet_image_path) }}" target="_blank" class="tich-link">View photo</a>
                            @else
                                -
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
                            @if (! $session->roster_verified_at && in_array($session->verification_status, ['draft', 'submitted', 'hod_verified'], true))
                                <form method="POST" action="{{ route('departments.academics.attendance-ledger.verify-roster', array_merge($hub, ['session' => $session->id])) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link">Verify roster</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="tich-text">No submitted attendance sessions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
