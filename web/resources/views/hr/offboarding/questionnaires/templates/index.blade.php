@extends('layouts.hr')

@section('title', 'Offboarding questionnaires')

@section('hr-content')
    <x-page-toolbar title="Questionnaire templates" meta="Assign templates by employment category (intern, contract, volunteer, etc.)">
        <x-slot:actions>
            <a href="{{ route('hr.offboarding.questionnaires.invitations.index') }}" class="tich-btn tich-btn-ghost">Sent questionnaires</a>
            <a href="{{ route('hr.offboarding.questionnaires.templates.create') }}" class="tich-btn tich-btn-primary">+ New template</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('success'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('success') }}</div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Employee types</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td><strong>{{ $template->title }}</strong></td>
                            <td class="tich-caption">
                                @foreach ($template->employment_categories ?? [] as $code)
                                    <span class="tich-badge">{{ $categoryLabels[$code] ?? \App\Support\EmploymentCategory::label($code) }}</span>
                                @endforeach
                            </td>
                            <td>{{ $template->questions_count }}</td>
                            <td>{{ $template->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <a href="{{ route('hr.offboarding.questionnaires.templates.edit', $template) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-table-empty">No templates yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($templates->hasPages())
            <div class="tich-mt-4">{{ $templates->links() }}</div>
        @endif
    </div>
@endsection