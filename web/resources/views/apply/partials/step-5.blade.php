<h2 class="tich-h3">Step 5 — Supporting documents</h2>
<p class="tich-text tich-mt-2">Upload PDF or image files (max 5 MB each). You may submit these now or provide them later if requested by admissions.</p>

@foreach ($documentTypes as $type => $label)
    <div class="tich-form-group tich-mt-4">
        <label for="documents_{{ $type }}" class="tich-label">{{ $label }}</label>
        <input type="file" id="documents_{{ $type }}" name="documents[{{ $type }}]" class="tich-input" accept=".pdf,.jpg,.jpeg,.png">
        @if (!empty($draft['documents'][$type]['original_filename']))
            <p class="tich-caption tich-mt-2">Uploaded: {{ $draft['documents'][$type]['original_filename'] }}</p>
        @endif
        @error("documents.{$type}")<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
@endforeach