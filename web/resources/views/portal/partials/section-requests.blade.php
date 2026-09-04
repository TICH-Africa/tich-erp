@php
    $lifecycleRequests = $lifecycleRequests ?? collect();
    $lifecycleTypes = $lifecycleTypes ?? \App\Models\StudentLifecycleRequest::TYPES;
@endphp

<x-page-toolbar title="Lifecycle requests" meta="Deferment, withdrawal, and readmission" />

<article class="tich-card tich-mt-8">
    <h2 class="tich-h3">Submit a request</h2>
    <form method="POST" action="{{ route('portal.lifecycle-requests.store') }}" class="tich-form-stack tich-mt-4">
        @csrf
        <div>
            <label for="request_type" class="tich-label">Request type</label>
            <select id="request_type" name="request_type" class="tich-select" required>
                @foreach ($lifecycleTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('request_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="effective_date" class="tich-label">Preferred effective date</label>
            <input id="effective_date" name="effective_date" type="date" class="tich-input" value="{{ old('effective_date') }}">
        </div>
        <div>
            <label for="reason" class="tich-label">Reason</label>
            <textarea id="reason" name="reason" rows="5" class="tich-input" required>{{ old('reason') }}</textarea>
        </div>
        <button type="submit" class="tich-btn tich-btn-primary">Submit request</button>
    </form>
</article>

<section class="tich-portal-panel tich-mt-8">
    <div class="tich-portal-panel__head">
        <h2 class="tich-h3">Your requests</h2>
    </div>
    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Effective</th>
                        <th>Submitted</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lifecycleRequests as $item)
                        <tr>
                            <td>{{ $item->typeLabel() }}</td>
                            <td>{{ ucfirst($item->status) }}</td>
                            <td>{{ optional($item->effective_date)->format('d M Y') ?: '-' }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y') }}</td>
                            <td class="tich-caption">{{ $item->reviewer_notes ?: \Illuminate\Support\Str::limit($item->reason, 80) }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No lifecycle requests yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
