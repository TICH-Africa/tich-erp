@extends('layouts.administration')

@section('title', 'Existing Students')

@section('administration-content')
    <style>
        .tich-lightbox { position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:9999; }
        .tich-lightbox--open { display:flex; }
        .tich-lightbox__backdrop { position:absolute; inset:0; background:rgba(0,0,0,.6); }
        .tich-lightbox__window { position:relative; max-width:90vw; max-height:90vh; z-index:1; }
        .tich-lightbox__img { max-width:100%; max-height:90vh; border-radius:8px; border:1px solid #fff; box-shadow:0 8px 32px rgba(0,0,0,.4); }
        .tich-lightbox__close { position:absolute; top:-10px; right:-10px; width:28px; height:28px; border:none; border-radius:50%; background:#fff; color:#000; font-size:20px; line-height:1; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,.3); }
    </style>

    <x-page-toolbar title="Existing Students" meta="All students registered directly">
        <x-slot:actions>
            <a href="{{ route('administration.applications.existing-student.create') }}" class="tich-btn tich-btn-primary">Add Existing Student</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="GET" class="tich-mb-4 tich-grid tich-grid--3">
        @include('partials.search-field', ['placeholder' => 'Reg number, name...', 'value' => request('search') ?? ''])
        <select name="cohort_intake" class="tich-input">
            <option value="">All cohorts</option>
            @foreach ($cohorts as $cohort)
                <option value="{{ $cohort }}" @selected(request('cohort_intake') == $cohort)>{{ $cohort }}</option>
            @endforeach
        </select>
        <select name="program_id" class="tich-input">
            <option value="">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->program_code }}</option>
            @endforeach
        </select>
    </form>

    <div class="tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Reg. Number</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Cohort</th>
                    <th>Campus</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td style="text-align:center; vertical-align:top; padding-top:0.5rem;">
                            @if ($student->photo_path)
                                @php($photoUrl = $student->photoUrl())
                                @if ($photoUrl)
                                    <a href="{{ $photoUrl }}"
                                       class="tich-student-photo"
                                       data-full="{{ $photoUrl }}"
                                       title="View profile photo">
                                        <img src="{{ $photoUrl }}" alt="{{ $student->fullName() }}"
                                             style="width:32px; height:32px; object-fit:cover; border-radius:50%; border:1px solid #e2e8f0; cursor:zoom-in;">
                                    </a>
                                @endif
                            @endif
                        </td>
                        <td>{{ $student->registration_number }}</td>
                        <td>{{ $student->fullName() }}</td>
                        <td>{{ $student->program?->program_name ?? '-' }}</td>
                        <td>{{ $student->cohort_intake }}</td>
                        <td>{{ $student->campus?->campus_name ?? '-' }}</td>
                        <td>{{ ucfirst($student->enrollment_status) }}</td>
                        <td>
                            <a href="{{ route('administration.applications.existing-student.show', $student->id) }}" class="tich-link">View</a>
                            <a href="{{ route('administration.applications.existing-student.edit', $student->id) }}" class="tich-link" style="display:block; margin-top:0.25rem;">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="tich-caption">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-4">{{ $students->links() }}</div>

    <div id="student-photo-lightbox" class="tich-lightbox" aria-hidden="true">
        <div class="tich-lightbox__backdrop" data-action="close"></div>
        <div class="tich-lightbox__window">
            <button type="button" class="tich-lightbox__close" data-action="close" aria-label="Close">&times;</button>
            <img class="tich-lightbox__img" src="" alt="Student photo">
        </div>
    </div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif

    <script>
    (function () {
        var box = document.getElementById('student-photo-lightbox');
        if (!box) return;
        var img = box.querySelector('.tich-lightbox__img');

        function open(src) {
            img.src = src;
            box.classList.add('tich-lightbox--open');
            box.setAttribute('aria-hidden', 'false');
        }

        function close() {
            img.src = '';
            box.classList.remove('tich-lightbox--open');
            box.setAttribute('aria-hidden', 'true');
        }

        box.addEventListener('click', function (e) {
            var t = e.target;
            if (t === box || t.matches('[data-action="close"]')) {
                e.preventDefault();
                close();
            }
        });

        document.addEventListener('click', function (e) {
            var link = e.target.closest('a.tich-student-photo');
            if (!link) return;
            e.preventDefault();
            open(link.getAttribute('data-full') || link.getAttribute('href'));
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && box.classList.contains('tich-lightbox--open')) {
                close();
            }
        });
    })();
    </script>
@endsection