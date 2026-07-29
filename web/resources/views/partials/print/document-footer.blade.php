<footer class="tich-doc-footer">
    <div>
        <strong>{{ $institution['short_name'] ?? 'TICH' }}</strong><br>
        {{ $institution['copyright'] ?? '' }}<br>
        @if (! empty($footerNote))
            {{ $footerNote }}
        @else
            This document was generated electronically from the TICH ERP academic records system.
        @endif
    </div>
    @if (! empty($showSignatures))
        <div class="tich-doc-footer__signatures">
            <p>Academic Registrar — signature &amp; stamp</p>
            <p>Date</p>
        </div>
    @endif
</footer>
