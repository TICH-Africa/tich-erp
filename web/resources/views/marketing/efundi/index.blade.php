@extends('layouts.marketing')

@section('title', 'e-Fundi opportunities')

@section('department-content')
    <x-page-toolbar title="e-Fundi opportunities" :meta="'Jobs, internships, and scholarships for students. Public: ' . $publicUrl">
        <a href="{{ route('marketing.efundi.create') }}" class="tich-btn tich-btn-primary">New opportunity</a>
    </x-page-toolbar>

    <div class="tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Students notified</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opportunities as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->title }}</strong>
                                <p class="tich-caption">{{ $row->organization_name ?? '—' }}</p>
                            </td>
                            <td>{{ $row->typeLabel() }}</td>
                            <td>
                                @if ($row->is_published)
                                    <span class="tich-badge tich-badge--success">Live</span>
                                @else
                                    <span class="tich-badge tich-badge--neutral">Draft</span>
                                @endif
                            </td>
                            <td>{{ $row->application_deadline?->format('j M Y') ?? '—' }}</td>
                            <td>{{ $row->students_notified_at?->format('j M Y, H:i') ?? '—' }}</td>
                            <td class="tich-table-actions">
                                @if ($row->is_published)
                                    <a class="tich-btn tich-btn-secondary tich-btn-sm" href="{{ route('e-fundi.show', $row) }}" target="_blank" rel="noopener">View</a>
                                @endif
                                <a class="tich-btn tich-btn-secondary tich-btn-sm" href="{{ route('marketing.efundi.edit', $row) }}">Edit</a>
                                @if ($row->is_published)
                                    <form method="post" action="{{ route('marketing.efundi.notify', $row) }}" style="display:inline;" data-allow-resubmit>
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-secondary tich-btn-sm">Notify again</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('marketing.efundi.destroy', $row) }}" style="display:inline;" onsubmit="return confirm('Delete this opportunity?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-btn tich-btn-danger tich-btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No opportunities yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection