{{-- blade/pages/admin/approvals.blade.php
    Route: GET  /admin/approvals           → ApprovalController@index
           POST /admin/approvals/{id}      → ApprovalController@update  (action=approve|reject|review)
    Middleware: auth, role:academic_registrar|ceo
    Variables: $applications (paginated collection), $counts (array), $isCEO (bool)
--}}
@extends('layouts.admin')

@section('title', 'Application Approvals – TICH ERP')
@section('page_title', 'Application Approval Queue')

@section('content')

@if($isCEO ?? false)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center gap-3 mb-5">
        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5C2.963 17.333 3.925 19 5.465 19z"/></svg>
        <p class="text-sm text-amber-800"><strong>CEO Override Mode:</strong> You can approve or reject any application regardless of prior review status.</p>
    </div>
@endif

{{-- Summary strip --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    @foreach([
        ['pending', 'Pending', '#d97706'],
        ['under_review', 'Under Review', '#1d4ed8'],
        ['approved', 'Approved', '#15803d'],
        ['rejected', 'Rejected', '#dc2626'],
    ] as [$key, $label, $color])
        <a href="?status={{ $key }}" class="bg-white border rounded-xl p-4 hover:shadow-sm transition-shadow {{ request('status') === $key ? 'border-2' : 'border-gray-100' }}"
           style="{{ request('status') === $key ? 'border-color:'.$color : '' }}">
            <p class="text-xs text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-extrabold mt-1" style="color:{{ $color }}">{{ $counts[$key] ?? 0 }}</p>
        </a>
    @endforeach
</div>

{{-- Filter & actions bar --}}
<div class="flex items-center gap-2 flex-wrap mb-5">
    @foreach(['all' => 'All', 'pending' => 'Pending', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $lbl)
        <a href="?status={{ $val }}"
           class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('status', 'all') === $val ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-green-50' }}">
            {{ $lbl }}
        </a>
    @endforeach
    <div class="ml-auto flex gap-2">
        <a href="{{ route('admin.approvals.export') }}" class="border border-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">Export CSV</a>
    </div>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                @foreach(['App ID', 'Student', 'Program', 'Applied', 'GPA', 'Nationality', 'Status', 'Actions'] as $h)
                    <th class="text-left py-3 px-4 text-gray-500 font-semibold">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $app)
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4 font-mono text-gray-500">{{ $app->app_id }}</td>
                    <td class="py-3 px-4">
                        <p class="font-semibold text-gray-800">{{ $app->student_name }}</p>
                        <p class="text-gray-400">{{ $app->email }}</p>
                    </td>
                    <td class="py-3 px-4 max-w-xs">
                        <p class="text-gray-800">{{ $app->program }}</p>
                        <p class="text-gray-400">{{ $app->faculty }}</p>
                    </td>
                    <td class="py-3 px-4 text-gray-500">{{ $app->applied_at->format('d M Y') }}</td>
                    <td class="py-3 px-4 font-semibold text-green-700">{{ $app->gpa ?? '—' }}</td>
                    <td class="py-3 px-4 text-gray-500">{{ $app->nationality }}</td>
                    <td class="py-3 px-4">
                        @php
                            $badgeMap = [
                                'pending'      => 'bg-amber-100 text-amber-800',
                                'under_review' => 'bg-blue-100 text-blue-800',
                                'approved'     => 'bg-green-100 text-green-800',
                                'rejected'     => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeMap[$app->status] ?? '' }}">
                            {{ ucfirst(str_replace('_', ' ', $app->status)) }}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        @if(in_array($app->status, ['pending', 'under_review']) || ($isCEO ?? false))
                            <div class="flex items-center gap-1.5">
                                <form method="POST" action="{{ route('admin.approvals.update', $app) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="bg-green-700 text-white text-xs px-2.5 py-1 rounded-lg hover:bg-green-800 transition-colors">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.approvals.update', $app) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="bg-red-100 text-red-600 text-xs px-2.5 py-1 rounded-lg hover:bg-red-200 transition-colors">Reject</button>
                                </form>
                                <a href="{{ route('admin.approvals.show', $app) }}" class="border border-gray-200 text-gray-500 text-xs px-2.5 py-1 rounded-lg hover:bg-gray-50 transition-colors">View</a>
                            </div>
                        @else
                            <a href="{{ route('admin.approvals.show', $app) }}" class="text-green-600 hover:underline text-xs">View</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-gray-400">No applications found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($applications->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $applications->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection
