@extends('layouts.finance')

@section('title', 'Projects & Donors')

@section('finance-content')
    <x-page-toolbar title="Projects & Donors" meta="Donor profiles, project profiles, project budgets, donor invoices, donor disbursements, USD-to-KES conversion and donor accountability reports">
        <x-slot:actions>
            <a href="{{ route('finance.projects-donors.create', $department) }}" class="tich-btn tich-btn-primary">+ New project</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search project, donor, or code…">
        <button type="submit" class="tich-btn tich-btn-secondary">Search</button>
    </form>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Donor</th>
                        <th>Grant</th>
                        <th>Disbursed (KES)</th>
                        <th>End date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td>
                                <a href="{{ route('finance.projects-donors.show', [$department, $project]) }}">
                                    <strong>{{ $project->project_name }}</strong>
                                </a>
                                <p class="tich-caption">{{ $project->project_code }}</p>
                            </td>
                            <td>
                                {{ $project->donor_name }}
                                <p class="tich-caption">{{ ucfirst(str_replace('_', ' ', $project->donor_type)) }}</p>
                            </td>
                            <td>
                                {{ $project->currency }} {{ number_format((float) $project->total_grant_amount, 2) }}
                            </td>
                            <td>KES {{ number_format((float) ($project->disbursed_kes ?? 0), 2) }}</td>
                            <td>{{ $project->end_date?->format('d M Y') ?? '-' }}</td>
                            <td>{{ ucfirst($project->status) }}</td>
                            <td>
                                <a href="{{ route('finance.projects-donors.show', [$department, $project]) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No projects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $projects->links() }}</div>
@endsection
