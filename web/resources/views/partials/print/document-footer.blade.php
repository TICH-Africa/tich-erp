<footer class="tich-doc-footer">
    <div class="tich-doc-footer__website">
        {{ $institution['website'] ?? 'tich.africa' }}
    </div>
    @if (! empty($showSignatures))
        @php
            $signatureLines = $signatureLabels ?? ['Academic Registrar - signature & stamp', 'Date'];
        @endphp
        <div class="tich-doc-footer__signatures">
            @foreach ($signatureLines as $label)
                <p>{{ $label }}</p>
            @endforeach
        </div>
    @endif
</footer>
