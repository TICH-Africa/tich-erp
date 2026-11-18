@extends('layouts.app')

@section('title', 'TICH Mobile App')
@section('meta_description', 'Download the TICH in Africa mobile app for Android and iOS. Sign in with your institution ERP account.')

@section('content')
    <x-animated-section animation="top">
        <section class="tich-section tich-section--white" aria-labelledby="mobile-app-heading">
            <div class="tich-container" style="max-width: 52rem;">
                <header class="tich-mb-8">
                    <h1 id="mobile-app-heading" class="tich-h1">TICH mobile app</h1>
                    <p class="tich-text tich-mt-2">
                        Employee, student, staff, and procurement portals on your phone—connected to the same TICH ERP account as the web.
                    </p>
                </header>

                <div class="tich-grid tich-grid--2" style="gap: 1.5rem; align-items: start;">
                    <x-animated-card animation="left">
                        <article class="tich-card tich-card--pad">
                            <h2 class="tich-h2">Android</h2>
                            @if ($android && ($android->hasDownloadableFile() || $android->external_url))
                                <p class="tich-text tich-mt-2">
                                    Version <strong>{{ $android->version_label }}</strong>
                                    @if ($android->build_number)
                                        (build {{ $android->build_number }})
                                    @endif
                                    @if ($android->formattedFileSize())
                                        — {{ $android->formattedFileSize() }}
                                    @endif
                                </p>
                                @if ($android->release_notes)
                                    <p class="tich-caption tich-mt-2">{!! nl2br(e($android->release_notes)) !!}</p>
                                @endif
                                @if ($android->hasDownloadableFile())
                                    <a href="{{ route('mobile-app.download', 'android') }}" class="tich-btn tich-btn-primary tich-mt-4" download>
                                        Download APK
                                    </a>
                                @endif
                                @if ($android->external_url)
                                    <a href="{{ $android->external_url }}" class="tich-btn {{ $android->hasDownloadableFile() ? 'tich-btn-secondary' : 'tich-btn-primary' }} tich-mt-3" target="_blank" rel="noopener noreferrer">
                                        Get on Google Play
                                    </a>
                                @endif
                            @else
                                <p class="tich-text tich-mt-2">Android download is not published yet. Check back soon or contact ICT.</p>
                            @endif

                            <h3 class="tich-h3 tich-mt-6">Install on Android</h3>
                            <ol class="tich-text tich-mt-2" style="padding-left: 1.25rem;">
                                <li>Download the APK using the button above (Wi‑Fi recommended).</li>
                                <li>If prompted, allow <strong>Install unknown apps</strong> for your browser or Files app.</li>
                                <li>Open the downloaded file and tap <strong>Install</strong>.</li>
                                <li>Open <strong>TICH in Africa</strong>, sign in with your institution email and password.</li>
                                <li>On first sign-in, enter the <strong>6-digit code</strong> sent to your email (valid 15 minutes).</li>
                            </ol>
                        </article>
                    </x-animated-card>

                    <x-animated-card animation="right">
                        <article class="tich-card tich-card--pad">
                            <h2 class="tich-h2">iPhone / iPad (iOS)</h2>
                            @if ($ios && ($ios->external_url || $ios->hasDownloadableFile()))
                                <p class="tich-text tich-mt-2">
                                    Version <strong>{{ $ios->version_label }}</strong>
                                    @if ($ios->build_number)
                                        (build {{ $ios->build_number }})
                                    @endif
                                </p>
                                @if ($ios->release_notes)
                                    <p class="tich-caption tich-mt-2">{!! nl2br(e($ios->release_notes)) !!}</p>
                                @endif
                                @if ($ios->external_url)
                                    <a href="{{ $ios->external_url }}" class="tich-btn tich-btn-primary tich-mt-4" target="_blank" rel="noopener noreferrer">
                                        Open in App Store / TestFlight
                                    </a>
                                @elseif ($ios->hasDownloadableFile())
                                    <a href="{{ route('mobile-app.download', 'ios') }}" class="tich-btn tich-btn-secondary tich-mt-4" download>
                                        Download IPA (enterprise)
                                    </a>
                                    <p class="tich-caption tich-mt-2">IPA installs require Mobile Device Management or an Apple enterprise profile from ICT.</p>
                                @endif
                            @else
                                <p class="tich-text tich-mt-2">iOS install link is not published yet. Use the web portals or contact ICT for TestFlight access.</p>
                            @endif

                            <h3 class="tich-h3 tich-mt-6">Install on iOS</h3>
                            <ol class="tich-text tich-mt-2" style="padding-left: 1.25rem;">
                                <li>Tap the App Store or TestFlight link above (when available).</li>
                                <li>Install <strong>TICH in Africa</strong>, then open the app.</li>
                                <li>Sign in with your institution email and password.</li>
                                <li>On first sign-in, enter the <strong>6-digit code</strong> emailed to you (expires in 15 minutes).</li>
                                <li>Allow notifications, location (for clock-in), and Face ID if you use app lock.</li>
                            </ol>
                        </article>
                    </x-animated-card>
                </div>

                <article class="tich-card tich-card--pad tich-mt-8">
                    <h2 class="tich-h2">Account &amp; support</h2>
                    <p class="tich-text tich-mt-2">
                        Accounts are issued by your institution (no self-registration in the app). For access problems, password resets, or a missing OTP email, contact ICT or HR.
                    </p>
                    <p class="tich-text tich-mt-2">
                        <a href="{{ route('privacy') }}">Privacy policy</a>
                        ·
                        <a href="{{ route('support') }}">Help &amp; support</a>
                        ·
                        <a href="{{ route('contact') }}">Contact</a>
                    </p>
                </article>
            </div>
        </section>
    </x-animated-section>
@endsection