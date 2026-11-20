@extends('layouts.app')

@section('title', 'TICH Mobile App')
@section('meta_description', 'Download the TICH in Africa mobile app for Android and iOS. Sign in with your institution ERP account.')

@section('content')
    <x-animated-section animation="top">
        <section class="tich-section tich-mobile-app-page" aria-labelledby="mobile-app-heading">
            <div class="tich-container" style="max-width: 52rem;">
                <header class="tich-mb-8">
                    <h1 id="mobile-app-heading" class="tich-h1">TICH mobile app</h1>
                    <p class="tich-text tich-mt-2">
                        Employee, student, staff, and procurement portals on your phone - connected to the same TICH ERP account as the web.
                    </p>
                </header>

                <div class="tich-grid tich-grid--2 tich-mobile-app-page__grid" style="gap: 2rem; align-items: start;">
                    <x-animated-card animation="left">
                        <article class="tich-mobile-app-panel">
                            <h2 class="tich-h2">Android</h2>
                            @if ($android && ($android->hasDownloadableFile() || $android->external_url))
                                <p class="tich-text tich-mt-2">
                                    Version <strong>{{ $android->version_label }}</strong>
                                    @if ($android->build_number)
                                        (build {{ $android->build_number }})
                                    @endif
                                    @if ($android->formattedFileSize())
                                        - {{ $android->formattedFileSize() }}
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

                            <div class="tich-mobile-app-install tich-mt-6">
                                <h3 class="tich-h3">How to install on Android</h3>
                                <ol class="tich-mobile-app-steps tich-mt-3">
                                    <li>Tap <strong>Download APK</strong> above. Use Wi-Fi if you can (the file is about 20-25 MB).</li>
                                    <li>When Android asks, turn on <strong>Install unknown apps</strong> for your browser or the Files app, then continue.</li>
                                    <li>Open the downloaded <strong>.apk</strong> file and tap <strong>Install</strong>.</li>
                                    <li>Open <strong>TICH in Africa</strong>. Sign in with your institution email and password.</li>
                                    <li><strong>First sign-in only:</strong> enter the 6-digit code emailed to you. The code expires in 15 minutes (check spam if you do not see it).</li>
                                </ol>
                            </div>
                        </article>
                    </x-animated-card>

                    <x-animated-card animation="right">
                        <article class="tich-mobile-app-panel">
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
                                    <p class="tich-caption tich-mt-2">IPA installs need a device profile or MDM from ICT.</p>
                                @endif
                            @else
                                <p class="tich-text tich-mt-2">iOS is not published here yet. Use the web portals or contact ICT for TestFlight access.</p>
                            @endif

                            <div class="tich-mobile-app-install tich-mt-6">
                                <h3 class="tich-h3">How to install on iOS</h3>
                                <ol class="tich-mobile-app-steps tich-mt-3">
                                    <li>When a link is available above, tap <strong>Open in App Store / TestFlight</strong> and install <strong>TICH in Africa</strong>.</li>
                                    <li>Open the app and sign in with your institution email and password.</li>
                                    <li><strong>First sign-in only:</strong> enter the 6-digit code emailed to you (expires in 15 minutes).</li>
                                    <li>Allow notifications if prompted (leave updates, alerts, and similar).</li>
                                    <li>Allow location when asked if you use clock-in on campus; allow Face ID if you enable app lock.</li>
                                </ol>
                            </div>
                        </article>
                    </x-animated-card>
                </div>

                <article class="tich-mobile-app-panel tich-mobile-app-install tich-mt-8">
                    <h2 class="tich-h2">Account and support</h2>
                    <ul class="tich-mobile-app-steps tich-mt-3">
                        <li>Institution staff issue your account. You cannot register yourself in the app.</li>
                        <li>For login problems, password reset, or a missing OTP email, contact <strong>ICT</strong> or <strong>HR</strong>.</li>
                    </ul>
                    <p class="tich-text tich-mt-4">
                        <a href="{{ route('privacy') }}">Privacy policy</a>
                        ·
                        <a href="{{ route('support') }}">Help and support</a>
                        ·
                        <a href="{{ route('contact') }}">Contact</a>
                    </p>
                </article>
            </div>
        </section>
    </x-animated-section>
@endsection