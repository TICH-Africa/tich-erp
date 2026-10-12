@extends('layouts.hr')

@section('title', $policy->title)

@section('hr-content')
    <x-page-toolbar :title="$policy->title">
        <x-slot:actions>
            <a href="{{ route('hr.policies.index') }}" class="tich-btn tich-btn-ghost">← Back to policies</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Policy Details</h3>
            <div class="tich-mt-4">
                <p><strong>Title:</strong> {{ $policy->title }}</p>
                <p><strong>Category:</strong> {{ ucfirst($policy->category) }}</p>
                <p><strong>Effective Date:</strong> {{ $policy->effective_date?->format('Y-m-d') ?? '-' }}</p>
                <p><strong>Expiry Date:</strong> {{ $policy->expiry_date?->format('Y-m-d') ?? '-' }}</p>
                <p><strong>Status:</strong> {{ $policy->is_active ? 'Active' : 'Inactive' }}</p>
                <p><strong>Target:</strong> {{ $policy->target_type === 'role' ? 'Existing audience' : ucfirst($policy->target_type) }}
                    @if ($policy->target_type === 'department' && $policy->target_departments)
                        {{ is_array($policy->target_departments) ? implode(', ', $policy->target_departments) : $policy->target_departments }}
                    @elseif ($policy->target_type === 'staff' && $policy->target_staff_ids)
                        ({{ count($policy->target_staff_ids) }} staff)
                    @endif
                </p>
                <p><strong>File:</strong> {{ $policy->original_filename }}</p>
                <p><strong>Uploaded By:</strong> {{ $policy->uploadedBy?->fullName() ?? '-' }}</p>
                <p><strong>Tags:</strong> {{ $policy->tags ?: '-' }}</p>
                @if ($policy->description)
                    <p class="tich-mt-4"><strong>Description:</strong></p>
                    <p class="tich-text tich-text--secondary">{{ $policy->description }}</p>
                @endif
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Actions</h3>
            <div class="tich-mt-4">
                <a href="{{ route('hr.policies.view', $policy) }}" class="tich-btn tich-btn-primary tich-mb-4" target="_blank">Open Document</a>
                <a href="{{ route('hr.policies.edit', $policy) }}" class="tich-btn tich-btn-secondary tich-mb-4">Edit & Replace</a>
                <a href="{{ route('hr.policies.send', $policy) }}" class="tich-btn tich-btn-secondary tich-mb-4">Send to staff</a>
                <a href="{{ route('hr.policies.acknowledgements', $policy) }}" class="tich-btn tich-btn-secondary tich-mb-4">Acknowledgements</a>
                <form method="POST" action="{{ route('hr.policies.destroy', $policy) }}" class="tich-mt-4" onsubmit="return confirm('Are you sure you want to delete this policy?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tich-btn tich-btn-ghost" style="color: #c53030; border-color: #c53030;">Delete</button>
                </form>
            </div>
        </article>
    </div>

    <article class="tich-card">
        <h3 class="tich-h3">Document Preview</h3>
        <div class="tich-mt-4" style="border: 1px solid var(--tich-neutral-border); border-radius: var(--radius-md); overflow: hidden; background: #f8fafc; min-height: 400px;">
            @if (str_contains($policy->mime_type, 'pdf'))
                <iframe src="{{ route('hr.policies.view', $policy) }}" width="100%" height="600px" style="border: none; display: block;"></iframe>
            @elseif (str_contains($policy->mime_type, 'image'))
                <img src="{{ route('hr.policies.view', $policy) }}" alt="{{ $policy->title }}" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
            @elseif (str_contains($policy->original_filename, '.doc') || str_contains($policy->original_filename, '.docx'))
                <div style="padding: 3rem; text-align: center;">
                    <p class="tich-text tich-text--secondary">Word document preview is only available on a public server.</p>
                    <a href="{{ route('hr.policies.download', $policy) }}" class="tich-btn tich-btn-primary tich-mt-4" target="_blank">Download to view</a>
                </div>
            @else
                <div style="padding: 3rem; text-align: center;">
                    <p class="tich-text tich-text--secondary">Preview not available for this file type.</p>
                    <a href="{{ route('hr.policies.download', $policy) }}" class="tich-btn tich-btn-primary tich-mt-4" target="_blank">Download to view</a>
                </div>
            @endif
        </div>
    </article>

    <article class="tich-card tich-mt-8">
        <h3 class="tich-h3">Sent To ({{ $acknowledgements->total() }} staff)</h3>
        <div class="tich-mt-4">
            @if ($acknowledgements->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="tich-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Read status</th>
                                <th>Read at</th>
                                <th>Status</th>
                                <th>Acknowledged At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($acknowledgements as $ack)
                                @php($readAt = $ack->is_acknowledged ? ($ack->acknowledged_at ?? $ack->read_at) : null)
                                <tr>
                                    <td>
                                        @if ($ack->staff)
                                            {{ $ack->staff->first_name }} {{ $ack->staff->surname }} ({{ $ack->staff->employee_number }})
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $ack->staff?->department?->dept_name ?? '-' }}</td>
                                    <td>{{ $ack->staff?->user?->roles?->pluck('display_name')->filter()->implode(', ') ?: '-' }}</td>
                                    <td>
                                        @if ($readAt)
                                            <span class="uf-badge" style="background: #dcfce7; color: #166534;">Read</span>
                                        @else
                                            <span class="uf-badge" style="background: #fef3c7; color: #92400e;">Not read</span>
                                        @endif
                                    </td>
                                    <td>{{ $readAt?->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>
                                        @if ($ack->is_acknowledged)
                                            <span class="uf-badge" style="background: #dcfce7; color: #166534;">Acknowledged</span>
                                        @else
                                            <span class="uf-badge" style="background: #fef3c7; color: #92400e;">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $ack->acknowledged_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $acknowledgements->links() }}
            @else
                <p class="tich-text tich-text--secondary">No staff members have been sent this policy yet.</p>
            @endif
        </div>
    </article>
@endsection
