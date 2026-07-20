@extends('layouts.app')

@section('title', 'Online Application')

@section('content')
<section class="tich-section">
    <div class="tich-container" style="max-width: 48rem;">
        <div class="tich-mb-8">
            <a href="{{ route('programs.index') }}" class="tich-link">&larr; Back to programmes</a>
            <h1 class="tich-h1 tich-mt-4">Online application portal</h1>
            <p class="tich-text tich-mt-2">Complete all steps to submit your application. Your response will be sent to the academic department for review.</p>
        </div>

        @include('apply.partials.progress', ['step' => $step, 'steps' => $steps])

        <div class="tich-card tich-mt-8">
            <form method="POST" action="{{ route('apply.step', $step) }}" enctype="multipart/form-data">
                @csrf

                @include('apply.partials.step-' . $step)

                <div class="tich-apply-actions tich-mt-8">
                    @if ($step > 1)
                        <button type="submit" name="action" value="back" class="tich-btn tich-btn-secondary">Back</button>
                    @endif

                    @if ($step < 5)
                        <button type="submit" name="action" value="next" class="tich-btn tich-btn-primary">Save &amp; continue</button>
                    @else
                        <button type="submit" name="action" value="submit" class="tich-btn tich-btn-primary">Submit application</button>
                    @endif
                </div>
            </form>
        </div>

        <form method="POST" action="{{ route('apply.reset') }}" class="tich-mt-4 tich-text-center">
            @csrf
            <button type="submit" class="tich-link" style="background:none;border:none;cursor:pointer;">Start over</button>
        </form>
    </div>
</section>
@endsection
