@if (session('status') || session('success'))
    <div class="tich-alert tich-alert--success" role="alert">
        {{ session('status') ?? session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="tich-alert tich-alert--error" role="alert">
        {{ session('error') }}
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div class="tich-alert tich-alert--error" role="alert">
        <p style="font-weight: 600; margin: 0 0 0.5rem;">Please fix the following:</p>
        <ul style="margin: 0; padding-left: 1.25rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
