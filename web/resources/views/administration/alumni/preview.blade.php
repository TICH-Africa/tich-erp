@extends('layouts.administration')

@section('title', 'Review the graduates list')

@section('administration-content')
    @php
        $rows = $preview['rows'];
        $counts = $preview['counts'];
    @endphp

    <x-page-toolbar title="Review the graduates list" meta="{{ $counts['mark'] }} line(s) ready to register out of {{ count($rows) }}">
        <x-slot:actions>
            <a href="{{ route('administration.alumni.upload') }}" class="tich-btn tich-btn-ghost">Choose another sheet</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-4">
        <ul class="tich-inline-stats" style="display:flex; gap:1.5rem; margin:0; padding:0; list-style:none; flex-wrap:wrap;">
            <li><strong>{{ $counts['mark'] }}</strong> <span class="tich-caption">will be registered</span></li>
            <li><strong>{{ $counts['already'] }}</strong> <span class="tich-caption">already on the register</span></li>
            <li><strong>{{ $counts['not_found'] }}</strong> <span class="tich-caption">no matching student</span></li>
            <li><strong>{{ $counts['invalid'] }}</strong> <span class="tich-caption">missing a year or date</span></li>
        </ul>
    </div>

    @if ($counts['mark'] === 0)
        <div class="tich-alert tich-alert--error tich-mt-4">
            Nothing on this sheet can be registered. Fix the listed lines, or add those students on
            <a href="{{ route('administration.applications.existing-student.create') }}">Add student</a> first and upload again.
        </div>
    @else
        <form method="POST" action="{{ route('administration.alumni.import') }}" class="tich-mt-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="tich-form-actions">
                <button type="submit" class="tich-btn tich-btn-primary">Register {{ $counts['mark'] }} graduate(s)</button>
            </div>
        </form>
    @endif

    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Line</th>
                        <th>Admission no.</th>
                        <th>Name</th>
                        <th>Year</th>
                        <th>Date</th>
                        <th>Award/class</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="tich-caption">{{ $row['line'] }}</td>
                            <td>{{ $row['registration_number'] ?: '-' }}</td>
                            <td><strong>{{ $row['name'] ?: '-' }}</strong></td>
                            <td class="tich-caption">{{ $row['graduation_year'] ?? '-' }}</td>
                            <td class="tich-caption">{{ $row['graduation_date'] ?? '-' }}</td>
                            <td class="tich-caption">{{ $row['graduation_award'] ?? '-' }}</td>
                            <td>
                                @php($label = ['mark' => 'Will register', 'already' => 'Already on register', 'not_found' => 'Not found', 'invalid' => 'Skipped'][$row['status']] ?? $row['status'])
                                <span class="tich-badge {{ $row['status'] === 'mark' ? 'tich-badge--success' : 'tich-badge--muted' }}">{{ $label }}</span>
                                <p class="tich-caption">{{ $row['note'] }}</p>
                                @if ($row['status'] === 'not_found')
                                    <a href="{{ route('administration.alumni.add', array_filter([
                                        'registration_number' => $row['registration_number'],
                                        'name' => $row['name'],
                                        'graduation_year' => $row['graduation_year'],
                                    ])) }}" class="tich-btn tich-btn-secondary" style="padding:0.3rem 0.6rem;font-size:0.82rem;">Add this graduate</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
