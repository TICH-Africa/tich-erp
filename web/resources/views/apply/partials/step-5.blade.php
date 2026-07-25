<h2 class="tich-h3">Step 5 - Supporting documents</h2>
<p class="tich-text tich-mt-2">Upload your supporting documents. Passport photo must be an image file. You may submit these now or provide them later if requested by admissions.</p>

@foreach ($documentTypes as $type => $label)
    @php
        $uploadRules = array_merge(
            config('tich-application.document_upload_rules.default', []),
            config("tich-application.document_upload_rules.{$type}", [])
        );
        $maxMb = number_format(((int) ($uploadRules['max_kb'] ?? 5120)) / 1024, 1);
    @endphp
    <div class="tich-form-group tich-mt-4">
        <label for="documents_{{ $type }}" class="tich-label">{{ $label }}</label>
        <input
            type="file"
            id="documents_{{ $type }}"
            name="documents[{{ $type }}]"
            class="tich-input js-apply-document"
            accept="{{ $uploadRules['accept'] ?? '.pdf,.jpg,.jpeg,.png' }}"
            data-doc-type="{{ $type }}"
            data-max-kb="{{ (int) ($uploadRules['max_kb'] ?? 5120) }}"
            data-images-only="{{ $type === 'passport_photo' ? '1' : '0' }}"
        >
        <p class="tich-caption tich-mt-2">{{ $uploadRules['hint'] ?? 'PDF or image (max '.$maxMb.' MB).' }}</p>
        @if (!empty($draft['documents'][$type]['original_filename']))
            <p class="tich-caption tich-mt-2">Uploaded: {{ $draft['documents'][$type]['original_filename'] }}</p>
        @endif
        @error("documents.{$type}")<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
@endforeach

@error('documents')<p class="tich-field-error tich-mt-4">{{ $message }}</p>@enderror

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form[enctype="multipart/form-data"]');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        if (event.submitter && event.submitter.value === 'back') return;

        var inputs = form.querySelectorAll('.js-apply-document');
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            if (!input.files || !input.files.length) continue;

            var file = input.files[0];
            var maxKb = parseInt(input.dataset.maxKb || '5120', 10);
            var imagesOnly = input.dataset.imagesOnly === '1';
            var maxBytes = maxKb * 1024;

            if (file.size > maxBytes) {
                event.preventDefault();
                alert('"' + file.name + '" is too large. Maximum allowed size is ' + (maxKb / 1024).toFixed(1).replace(/\.0$/, '') + ' MB.');
                input.focus();
                return;
            }

            if (imagesOnly && !file.type.startsWith('image/')) {
                event.preventDefault();
                alert('Passport photo must be an image (JPEG, PNG, or WebP). PDFs and other file types are not accepted.');
                input.focus();
                return;
            }
        }
    });
});
</script>
