<footer class="tich-doc-footer">
    <div class="tich-doc-footer__website">
        {{ $institution['website'] ?? 'tich.africa' }}
    </div>
    @if (! empty($showSignatures))
        <div class="tich-doc-footer__signatures">
            <p>Academic Registrar - signature &amp; stamp</p>
            <p>Date</p>
        </div>
    @endif
</footer>
