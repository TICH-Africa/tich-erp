@extends('layouts.app')

@section('title', 'Online Application')
@section('meta_description', config('tich-seo.pages.apply.description'))
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="tich-section" aria-labelledby="apply-heading">
    <div class="tich-container" style="max-width: 48rem;">
        <div class="tich-mb-8">
            <a href="{{ route('programs.index') }}" class="tich-link">&larr; Back to programmes</a>
            <h1 id="apply-heading" class="tich-h1 tich-mt-4">Online application portal</h1>
            <p class="tich-text tich-mt-2">Complete all steps to submit your application. Your response will be sent to the academic department for review.</p>
        </div>

        @include('apply.partials.progress', ['step' => $step, 'steps' => $steps])

        <div class="uf-form tich-mt-8">
            <form method="POST" action="{{ route('apply.step', $step) }}" enctype="multipart/form-data" data-apply-form data-uf="ready">
                @csrf
                <input type="hidden" name="action" value="save">

                <div class="uf-amount-bar">
                    <div>
                        <div class="uf-amount-bar__ref">
                            Step {{ $step }} of {{ count($steps) }}
                            @if (! empty($steps[$step]['label']))
                                · {{ $steps[$step]['label'] }}
                            @endif
                        </div>
                        <div class="uf-amount-bar__sum">Online application</div>
                    </div>
                    <span class="uf-badge">In progress</span>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-body">
                        @include('apply.partials.step-' . $step)

                        <div class="uf-form-actions tich-apply-actions">
                            @if ($step > 1)
                                <button type="submit" data-apply-action="back" class="uf-btn uf-btn-secondary">Back</button>
                            @endif

                            @if ($step < 7)
                                <button type="submit" data-apply-action="next" class="uf-btn uf-btn-primary">Save &amp; continue</button>
                            @else
                                <button type="submit" data-apply-action="submit" class="uf-btn uf-btn-primary">Submit application</button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.querySelector('[data-apply-form]');
                if (!form) return;

                var actionInput = form.querySelector('input[name="action"]');
                var buttons = form.querySelectorAll('[data-apply-action]');

                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (actionInput) {
                            actionInput.value = btn.getAttribute('data-apply-action') || 'save';
                        }
                    });
                });
            });
        </script>

        <form method="POST" action="{{ route('apply.reset') }}" class="tich-mt-4 tich-text-center" data-uf="skip">
            @csrf
            <button type="submit" class="tich-link" style="background:none;border:none;cursor:pointer;">Start over</button>
        </form>
    </div>
</section>
@endsection
