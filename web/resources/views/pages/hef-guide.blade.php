@extends('layouts.app')

@section('title', 'How to apply HEF')
@section('meta_description', 'Step by step guide to applying for Higher Education Funding (HEF) in Kenya, from the Higher Education Finance Application guide.')

@section('content')
    <x-animated-section animation="top">
        <section class="tich-section" aria-labelledby="hef-heading">
            <div class="tich-container">
                <header class="tich-mb-8">
                    <h1 id="hef-heading" class="tich-h1">Higher Education Finance Application guide</h1>
                    <p class="tich-text tich-mt-2">
                        HEF (Higher Education Funding) is Government of Kenya funding for university and college students.
                        These are the steps printed in the Higher Education Finance Application guide, in the order the guide gives them.
                    </p>
                    <div class="tich-flex tich-gap-3 tich-mt-6" style="flex-wrap: wrap;">
                        <a href="{{ $portals[0]['url'] }}" target="_blank" rel="noopener noreferrer" class="tich-btn tich-btn-primary">
                            Apply on the HEF Student Portal
                        </a>
                        <a href="{{ route('apply.index') }}" class="tich-btn tich-btn-secondary">
                            Apply for a TICH programme
                        </a>
                        <a href="{{ route('programs.index') }}" class="tich-btn tich-btn-ghost">
                            Browse programs &amp; courses
                        </a>
                    </div>
                </header>

                <div class="tich-card tich-mb-8">
                    <div class="tich-card__body">
                        <h2 class="tich-h3">You will need</h2>
                        <p class="tich-text tich-mt-2">
                            Your National ID number, or your KCSE index number and KCSE exam year if you do not have a National ID,
                            the KUCCPS programme code printed on your College/University admission letter, an email address you can log into,
                            and a telephone number that can receive a verification code.
                        </p>
                    </div>
                </div>

                <ol class="hef-steps">
                    @foreach ($steps as $index => $step)
                        <li class="hef-steps__item">
                            <span class="hef-steps__number" aria-hidden="true">{{ $index + 1 }}</span>
                            <div class="hef-steps__body">
                                <h2 class="hef-steps__title">{{ $step['title'] }}</h2>

                                @if (! empty($step['notes']))
                                    <ul class="hef-steps__notes">
                                        @foreach ($step['notes'] as $note)
                                            <li>{{ $note }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @foreach ($step['branches'] ?? [] as $branch)
                                    <div class="hef-steps__branch">
                                        <h3 class="hef-steps__branch-title">
                                            Do you have a National ID Number? {{ $branch['label'] }}
                                        </h3>
                                        <ul class="hef-steps__notes">
                                            @foreach ($branch['notes'] as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="tich-card tich-mt-8">
                    <div class="tich-card__body">
                        <h2 class="tich-h3">HEF portals</h2>
                        <p class="tich-text tich-mt-2">The guide lists these three addresses. Any one of them opens the Student Portal.</p>
                        <ul class="hef-steps__notes tich-mt-4">
                            @foreach ($portals as $portal)
                                <li>
                                    <a href="{{ $portal['url'] }}" target="_blank" rel="noopener noreferrer">{{ $portal['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <p class="tich-text tich-text--sm tich-text--muted tich-mt-4">
                            HEF is Government of Kenya funding applied for on the HEF portal above. TICH does not decide an HEF application.
                            For TICH scholarships, grants and work-study rounds see
                            <a href="{{ route('financial-aid.index') }}">Financial Aid</a>.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </x-animated-section>
@endsection
