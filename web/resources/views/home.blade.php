@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-emerald-800 via-teal-800 to-slate-900 text-white">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute -left-24 top-0 h-80 w-80 rounded-full bg-emerald-400 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-teal-300 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="max-w-3xl">
                <p class="mb-4 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-emerald-100 backdrop-blur">
                    Enterprise Resource Planning Platform
                </p>
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    TICH ERP
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-emerald-100/90 sm:text-xl">
                    A unified system for admissions, academics, finance, and human resources across the Tropical Institute of Community Health and Development in Africa.
                </p>

                <div class="mt-10 flex flex-wrap gap-4">
                    @auth
                        <span class="rounded-lg bg-white px-6 py-3 text-sm font-semibold text-emerald-900 shadow-sm">
                            Signed in as {{ auth()->user()->username }}
                        </span>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg bg-white px-6 py-3 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50">
                            Sign in to portal
                        </a>
                        <a href="{{ route('register') }}" class="rounded-lg border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            Create account
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Core modules</h2>
            <p class="mt-3 text-slate-600">Everything your institution needs, connected in one platform.</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900">Administration</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Admissions, student records, SACCO, and compliance.</p>
                <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Coming soon</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 text-teal-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900">Academics</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Curriculum, attendance, assessments, and examinations.</p>
                <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Coming soon</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-cyan-200 hover:shadow-md">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900">Finance & Fees</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Student accounts, invoicing, procurement, and payroll.</p>
                <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Coming soon</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900">Human Resources</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Staff lifecycle, leave, recruitment, and performance.</p>
                <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Coming soon</p>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-2 lg:gap-16">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Built for community health education</h2>
                    <p class="mt-4 text-slate-600 leading-relaxed">
                        TICH ERP supports multi-campus operations — from the main campus to community colleges and sub-county hubs — with workflows tailored to TVET, nursing, and public health programmes.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['label' => 'Campuses', 'value' => 'Multi-site'],
                        ['label' => 'Students', 'value' => 'Admissions to alumni'],
                        ['label' => 'Security', 'value' => 'RBAC + MFA'],
                        ['label' => 'Status', 'value' => 'In development'],
                    ] as $stat)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
