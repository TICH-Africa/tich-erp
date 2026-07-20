@extends('layouts.admin')

@section('title', 'Campuses')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Campuses</h1>
    <p class="tich-text tich-mb-8">Multi-campus structure for TICH main campus, community colleges, and sub-county hubs.</p>

    <div class="tich-grid tich-grid--2" style="align-items: start; gap: 2rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Add campus</h2>
            <form method="POST" action="{{ route('admin.campuses.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Campus code</label>
                    <input type="text" name="campus_code" class="tich-input" value="{{ old('campus_code') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Campus name</label>
                    <input type="text" name="campus_name" class="tich-input" value="{{ old('campus_name') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Type</label>
                    <select name="campus_type" class="tich-input" required>
                        @foreach ($campusTypes as $type)
                            <option value="{{ $type }}" @selected(old('campus_type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Parent campus</label>
                    <select name="parent_campus_id" class="tich-input">
                        <option value="">None</option>
                        @foreach ($parentCampuses as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_campus_id') == $parent->id)>{{ $parent->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">County</label>
                    <input type="text" name="county" class="tich-input" value="{{ old('county') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Physical address</label>
                    <textarea name="physical_address" class="tich-input" rows="2">{{ old('physical_address') }}</textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Create campus</button>
            </form>
        </article>

        <div class="tich-card" style="overflow-x: auto;">
            <h2 class="tich-h3">Existing campuses</h2>
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Type</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($campuses as $campus)
                        <tr>
                            <td>{{ $campus->campus_code }}</td>
                            <td>{{ $campus->campus_name }}</td>
                            <td>{{ str_replace('_', ' ', $campus->campus_type) }}</td>
                            <td>{{ $campus->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No campuses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
