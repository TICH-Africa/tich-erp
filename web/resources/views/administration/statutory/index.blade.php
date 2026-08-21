@extends('layouts.administration')

@section('title', 'Statutory tracking')

@section('administration-content')
    <x-page-toolbar title="Statutory tracking" meta="Centralized certifications and alignment monitoring">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="statutory-create-modal">+ Add certificate</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Authority</th>
                        <th>Number</th>
                        <th>Issued</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($certs as $cert)
                        <tr>
                            <td><strong>{{ $cert->certificate_code }}</strong></td>
                            <td>{{ $cert->title }}</td>
                            <td>{{ $cert->authority }}</td>
                            <td class="tich-caption">{{ $cert->certificate_number ?? '-' }}</td>
                            <td class="tich-caption">{{ $cert->issued_on?->format('d M Y') ?? '-' }}</td>
                            <td class="tich-caption">{{ $cert->expires_on?->format('d M Y') ?? '-' }}</td>
                            <td>
                                <span class="tich-badge {{ $cert->status === 'expired' ? 'tich-badge--danger' : ($cert->status === 'expiring' ? 'tich-badge--warning' : 'tich-badge--success') }}">
                                    {{ ucfirst(str_replace('_', ' ', $cert->status)) }}
                                </span>
                            </td>
                            <td>
                                @if ($cert->document_path)
                                    <a href="{{ route('administration.statutory.download', $cert) }}" class="tich-link">Download</a>
                                @else
                                    <span class="tich-caption">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 8, 'title' => 'No statutory certificates recorded', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($certs instanceof \Illuminate\Contracts\Pagination\Paginator && $certs->hasPages())
            <div class="tich-mt-4">{{ $certs->links() }}</div>
        @endif
    </div>

    <div id="statutory-create-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="statutory-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin:0;">Record certification</h2>
                <button type="button" class="tich-modal__close" data-close-modal="statutory-create-modal">&times;</button>
            </header>
            <form method="POST" action="{{ route('administration.statutory.store') }}" class="tich-modal__body" enctype="multipart/form-data">
                @csrf
                <div class="tich-form-stack">
                    <div class="tich-form-group">
                        <label class="tich-label">Title</label>
                        <input type="text" name="title" class="tich-input" value="{{ old('title') }}" required maxlength="300">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Authority</label>
                        <input type="text" name="authority" class="tich-input" value="{{ old('authority') }}" required maxlength="255" placeholder="e.g. Kenya Revenue Authority, TVETA, Ministry of Education">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Certificate number</label>
                        <input type="text" name="certificate_number" class="tich-input" value="{{ old('certificate_number') }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Issued on</label>
                        <input type="date" name="issued_on" class="tich-input" value="{{ old('issued_on') }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Expires on</label>
                        <input type="date" name="expires_on" class="tich-input" value="{{ old('expires_on') }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Certificate file</label>
                        <input type="file" name="certificate_file" class="tich-input" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*">
                        <p class="tich-caption tich-mt-1">PDF or image, max 10 MB</p>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Alignment notes</label>
                        <textarea name="alignment_notes" class="tich-input" rows="3">{{ old('alignment_notes') }}</textarea>
                    </div>
                </div>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="statutory-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
@endsection
