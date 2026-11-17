@extends('layouts.research')

@section('title', 'Research resources')

@section('research-content')
    <x-page-toolbar title="Research resources" meta="Books, papers and reports posted for download on the public Research page">
        <x-slot:actions>
            <a href="{{ route('research.resources.create') }}" class="tich-btn tich-btn-primary">Add resource</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Title</th>
                        <th>Authors</th>
                        <th>Publisher</th>
                        <th>File</th>
                        <th>On website</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resources as $resource)
                        <tr>
                            <td>{{ $resource->year }}</td>
                            <td><strong>{{ $resource->title }}</strong></td>
                            <td>{{ $resource->authors }}</td>
                            <td>{{ $resource->publisher }}</td>
                            <td>
                                @if ($resource->hasFile())
                                    {{ $resource->original_name ?: 'On record' }}
                                @else
                                    <span class="tich-caption">No file</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$resource->is_active ? 'published' : 'draft'" :label="$resource->is_active ? 'Yes' : 'Hidden'" /></td>
                            <td>
                                <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                                    <a href="{{ route('research.resources.edit', $resource) }}" class="tich-link">Edit</a>
                                    <form method="POST" action="{{ route('research.resources.destroy', $resource) }}" onsubmit="return confirm('Remove this resource and its file from the website?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tich-btn tich-btn-ghost">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No resources yet. <a href="{{ route('research.resources.create') }}" class="tich-link">Add the first one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
