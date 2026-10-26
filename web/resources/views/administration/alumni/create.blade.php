@extends('layouts.administration')

@section('title', 'Register a graduate')

@section('administration-content')
    <x-page-toolbar title="Register a graduate" meta="Find the person on the student list first - a graduate is that same record, marked finished">
        <x-slot:actions>
            <a href="{{ route('administration.alumni.index') }}" class="tich-btn tich-btn-ghost">Back to the register</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" action="{{ route('administration.alumni.create') }}" class="tich-page-toolbar__filters-form" data-live-filter>
                @include('partials.search-field', ['placeholder' => 'Admission no. or name…', 'value' => $search])
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    @if ($search === '')
        <p class="tich-text tich-mt-4">Type an admission number or a name to find the student.</p>
    @elseif ($students->isEmpty())
        <div class="tich-card tich-mt-4">
            <p class="tich-text">No current student matches “{{ $search }}”.</p>
            <p class="tich-caption tich-mt-2">
                If they graduated before the school kept records here, add them on
                <a href="{{ route('administration.applications.existing-student.create') }}">Add student</a> first, then mark them finished here.
            </p>
        </div>
    @else
        <div class="tich-card tich-table-panel tich-mt-4">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Admission no.</th>
                            <th>Programme</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td><strong>{{ $student->displayName() }}</strong></td>
                                <td class="tich-caption">{{ $student->registration_number ?: '-' }}</td>
                                <td class="tich-caption">{{ $student->program?->program_name ?? '-' }}</td>
                                <td class="tich-caption">{{ ucfirst((string) $student->enrollment_status) }}</td>
                                <td>
                                    <a href="{{ route('administration.alumni.edit', $student) }}" class="tich-btn tich-btn-primary" style="padding:0.32rem 0.6rem;font-size:0.84rem;">Mark graduated</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($students->count() >= 60)
                <p class="tich-caption tich-mt-3">Showing the first 60 matches - narrow the search to see the rest.</p>
            @endif
        </div>
    @endif
@endsection
