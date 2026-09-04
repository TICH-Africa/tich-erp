@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <x-page-toolbar title="Course evaluations" meta="Open and close student evaluation windows">
        <x-slot:actions>
            <a href="{{ route('departments.academics.dashboard', $hub) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">New evaluation window</h2>
        <form method="POST" action="{{ route('departments.academics.evaluation-windows.store', $hub) }}" class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem; align-items:end;">
            @csrf
            <div>
                <label for="title" class="tich-label">Title</label>
                <input id="title" name="title" type="text" class="tich-input" required value="{{ old('title') }}">
            </div>
            <div>
                <label for="semester_id" class="tich-label">Semester ID (optional)</label>
                <input id="semester_id" name="semester_id" type="number" class="tich-input" value="{{ old('semester_id') }}">
            </div>
            <div>
                <label for="opens_at" class="tich-label">Opens at</label>
                <input id="opens_at" name="opens_at" type="datetime-local" class="tich-input" required value="{{ old('opens_at') }}">
            </div>
            <div>
                <label for="closes_at" class="tich-label">Closes at</label>
                <input id="closes_at" name="closes_at" type="datetime-local" class="tich-input" required value="{{ old('closes_at') }}">
            </div>
            <div>
                <label class="tich-label">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active
                </label>
            </div>
            <div>
                <button type="submit" class="tich-btn tich-btn-primary">Create window</button>
            </div>
        </form>
    </article>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Window</th>
                        <th>Active</th>
                        <th>Responses</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($windows as $window)
                        <tr>
                            <td>{{ $window->title }}</td>
                            <td class="tich-caption">
                                {{ $window->opens_at?->format('d M Y H:i') }}
                                –
                                {{ $window->closes_at?->format('d M Y H:i') }}
                            </td>
                            <td>{{ $window->is_active ? 'Yes' : 'No' }}</td>
                            <td>{{ $window->evaluations_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('departments.academics.evaluation-windows.update', array_merge($hub, ['evaluationWindow' => $window->id])) }}" class="tich-flex-wrap" style="gap:0.5rem; align-items:end;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="title" value="{{ $window->title }}">
                                    <input type="hidden" name="semester_id" value="{{ $window->semester_id }}">
                                    <input type="hidden" name="opens_at" value="{{ optional($window->opens_at)->format('Y-m-d\TH:i') }}">
                                    <input type="hidden" name="closes_at" value="{{ optional($window->closes_at)->format('Y-m-d\TH:i') }}">
                                    <label class="tich-caption">
                                        <input type="checkbox" name="is_active" value="1" @checked($window->is_active)> Active
                                    </label>
                                    <button type="submit" class="tich-btn tich-btn-ghost tich-btn--compact">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No evaluation windows', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($windows, 'hasPages') && $windows->hasPages())
            <div class="tich-mt-4">{{ $windows->links() }}</div>
        @endif
    </div>
@endsection
