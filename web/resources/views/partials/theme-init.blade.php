<script>
(function () {
    try {
        if (localStorage.getItem('tich-theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    } catch (e) {}
})();
</script>
