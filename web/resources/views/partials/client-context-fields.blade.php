<input type="hidden" name="client_device_type" value="">
<input type="hidden" name="client_browser" value="">
<input type="hidden" name="client_os" value="">
<input type="hidden" name="client_timezone" value="">
<input type="hidden" name="client_screen" value="">
<input type="hidden" name="client_language" value="">
<input type="hidden" name="client_connection_type" value="">
<input type="hidden" name="client_connection_effective_type" value="">
<input type="hidden" name="client_connection_downlink" value="">
<input type="hidden" name="client_online" value="">

<script>
(function () {
    function detectDeviceType() {
        var ua = navigator.userAgent || '';

        if (/Mobi|Android.*Mobile|iPhone|iPod/i.test(ua)) {
            return 'mobile';
        }

        if (/iPad|Tablet|Kindle|Silk|Android(?!.*Mobile)/i.test(ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    function populateForm(form) {
        var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

        var values = {
            client_device_type: detectDeviceType(),
            client_browser: navigator.userAgent || '',
            client_os: navigator.platform || '',
            client_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
            client_screen: window.screen ? window.screen.width + 'x' + window.screen.height : '',
            client_language: navigator.language || '',
            client_connection_type: connection && connection.type ? connection.type : '',
            client_connection_effective_type: connection && connection.effectiveType ? connection.effectiveType : '',
            client_connection_downlink: connection && connection.downlink ? String(connection.downlink) : '',
            client_online: navigator.onLine ? '1' : '0',
        };

        Object.keys(values).forEach(function (name) {
            var input = form.querySelector('[name="' + name + '"]');

            if (input) {
                input.value = values[name];
            }
        });
    }

    function initForms() {
        document.querySelectorAll('form[data-client-context]').forEach(function (form) {
            populateForm(form);
            form.addEventListener('submit', function () {
                populateForm(form);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initForms);
    } else {
        initForms();
    }
})();
</script>
