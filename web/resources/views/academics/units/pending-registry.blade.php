@extends('layouts.academics')

@section('title', 'Pending units - Registry approval')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
    @endphp

    <div class="tich-section__intro" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; text-align:left;">
        <div>
            <h1 class="tich-h1" style="font-size: 2rem;">Pending units for approval</h1>
            <p class="tich-text">Review units submitted by HODs awaiting registry verification.</p>
        </div>
    </div>

    <div class="tich-card tich-mt-8" style="overflow-x:auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Unit</th>
                    <th>Department</th>
                    <th>Program</th>
                    <th>Submitted at</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td>{{ $unit->unit_code }}</td>
                        <td>{{ $unit->unit_name }}</td>
                        <td>{{ $unit->department?->dept_name ?? ($unit->program?->department->dept_name ?? '-') }}</td>
                        <td>{{ $unit->program?->program_name ?? '-' }}</td>
                        <td>{{ $unit->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td style="white-space:nowrap;">
                            <form method="POST" action="{{ route('departments.academics.units.approve', array_merge($hub, ['unit' => $unit->id])) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="tich-btn tich-btn-primary tich-btn-sm">Approve</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:2rem;text-align:center;" class="tich-text">No units pending registry approval.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection