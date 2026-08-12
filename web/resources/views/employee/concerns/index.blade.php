@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar
        title="Concerns &amp; issues"
        meta="Raise workplace concerns anytime - HR will review and follow up"
    >
        <x-slot:actions>
            <a href="{{ route('employee.concerns.create') }}" class="tich-btn tich-btn-primary">+ Raise a concern</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-6" style="border-left:4px solid var(--tich-blue);">
        <h2 class="tich-h3">Need help with something at work?</h2>
        <p class="tich-text tich-mt-2">
            Use this channel to report concerns, issues, or problems affecting you at work.
            Submissions go directly to HR. You can track status here until the matter is resolved.
        </p>
        <div class="tich-flex tich-mt-4" style="gap:0.75rem; flex-wrap:wrap;">
            <a href="{{ route('employee.concerns.create') }}" class="tich-btn tich-btn-primary">Raise a concern</a>
            <a href="{{ route('employee.relations.feedback.create') }}" class="tich-btn tich-btn-ghost">Share feedback or suggestion</a>
        </div>
    </article>

    @if ($openCount > 0)
        <p class="tich-caption tich-mt-6">{{ $openCount }} open concern(s) being handled by HR.</p>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap:wrap; gap:0.75rem;">
            <h2 class="tich-h3" style="margin:0;">My submitted concerns</h2>
        </div>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($concerns as $concern)
                        <tr>
                            <td><strong>{{ $concern->reference_number ?? '#'.$concern->id }}</strong></td>
                            <td>{{ $concern->subject ?? \Illuminate\Support\Str::limit($concern->description, 60) }}</td>
                            <td class="tich-caption">{{ $concern->categoryLabel() }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($concern->status) {
                                    'open' => 'warning',
                                    'under_review' => 'info',
                                    'resolved' => 'success',
                                    'closed' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ $concern->statusLabel() }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $concern->created_at?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('employee.concerns.show', $concern) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No concerns submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($concerns->hasPages())
            <div class="tich-mt-4">{{ $concerns->links() }}</div>
        @endif
    </div>
@endsection
