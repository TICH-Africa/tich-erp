{{-- blade/pages/admin/dashboard.blade.php
    Route: GET /admin/dashboard  →  DashboardController@index
    Middleware: auth
    Variables: $user (Auth::user()), $stats (array), $recentActivity (collection)
    The controller resolves role-specific data and passes it to this view.
    For heavy charts, prefer loading data via Livewire or AJAX (Axios) to keep
    initial page payload small.
--}}
@extends('layouts.admin')

@section('title', 'Dashboard – TICH ERP')
@section('page_title', 'Dashboard')

@section('content')

{{-- Welcome banner --}}
<div class="bg-gradient-to-r from-green-800 to-green-700 rounded-xl p-5 text-white mb-5">
    <p class="text-xs text-green-200 font-medium mb-1">{{ now()->format('l, d F Y') }}</p>
    <h2 class="text-xl font-extrabold">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}</h2>
    <p class="text-sm text-green-200 mt-1">You are logged in as <strong>{{ auth()->user()->role_label }}</strong>.</p>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    @foreach($stats ?? [] as $stat)
        <div class="bg-white border border-gray-100 rounded-xl p-4 hover:shadow-sm transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium">{{ $stat['label'] }}</p>
                    <p class="text-2xl font-extrabold mt-1 text-gray-900">{{ $stat['value'] }}</p>
                    @if(isset($stat['sub']))
                        <p class="text-xs mt-1 {{ ($stat['trend'] ?? '') === 'up' ? 'text-green-600' : (($stat['trend'] ?? '') === 'down' ? 'text-red-500' : 'text-gray-400') }}">
                            {{ $stat['sub'] }}
                        </p>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:{{ ($stat['color'] ?? '#15803d') }}18">
                    <span style="color:{{ $stat['color'] ?? '#15803d' }}">
                        {!! $stat['icon'] ?? '' !!}
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Role-specific content section --}}
@yield('dashboard_content')

{{-- Recent Activity (default for all roles) --}}
<div class="bg-white border border-gray-100 rounded-xl p-5 mt-5">
    <div class="flex items-end justify-between mb-4">
        <div>
            <h3 class="text-sm font-bold text-gray-900">Recent Activity</h3>
            <p class="text-xs text-gray-400 mt-0.5">Latest system actions in your area</p>
        </div>
        <a href="{{ route('admin.reports.activity') }}" class="text-xs text-green-600 hover:underline">View All</a>
    </div>
    <div class="space-y-0">
        @forelse($recentActivity ?? [] as $activity)
            <div class="flex items-start justify-between py-2.5 border-b border-gray-50 last:border-0">
                <div class="flex items-start gap-2.5">
                    <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" style="background:{{ $activity->color ?? '#15803d' }}"></div>
                    <div>
                        <p class="text-xs font-medium text-gray-800">{{ $activity->description }}</p>
                        <p class="text-xs text-gray-400">{{ $activity->user }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 flex-shrink-0 ml-3">{{ $activity->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-xs text-gray-400 py-4 text-center">No recent activity to display.</p>
        @endforelse
    </div>
</div>

@endsection
