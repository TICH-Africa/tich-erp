<script>
(function () {
    try {
        var stored = localStorage.getItem('tich-theme');
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        var useDark = stored === 'dark' || ((stored === null || stored === 'system') && prefersDark);

        if (useDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    } catch (e) {}
})();
</script>
