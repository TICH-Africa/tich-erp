@extends('layouts.research')

@section('title', 'Partners on website')

@section('research-content')
    <x-page-toolbar title="Partners on website" meta="The organisations TICH works with, shown on the public Research page">
        <x-slot:actions>
            <a href="{{ route('research.partners.create') }}" class="tich-btn tich-btn-primary">Add partner</a>
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
                        <th>Logo</th>
                        <th>Partner</th>
                        <th>On website</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partners as $partner)
                        <tr>
                            <td>
                                @if ($partner->logoUrl())
                                    <img src="{{ $partner->logoUrl() }}" alt="" style="width:5rem;height:2.5rem;object-fit:contain;border-radius:4px;background:#e8eaed;">
                                @else
                                    <span class="tich-caption">No logo</span>
                                @endif
                            </td>
                            <td>
                                @if ($partner->name)
                                    <strong>{{ $partner->name }}</strong>
                                @else
                                    <span class="tich-caption">Name on the logo</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$partner->is_active ? 'published' : 'draft'" :label="$partner->is_active ? 'Yes' : 'Hidden'" /></td>
                            <td>
                                <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                                    <a href="{{ route('research.partners.edit', $partner) }}" class="tich-link">Edit</a>
                                    <form method="POST" action="{{ route('research.partners.destroy', $partner) }}" onsubmit="return confirm('Remove {{ $partner->displayLabel() }} from the website partners list?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tich-btn tich-btn-ghost">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No partners yet. <a href="{{ route('research.partners.create') }}" class="tich-link">Add the first one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
