(function () {
    var DEBOUNCE_MS = 1500;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? meta.getAttribute('content') : '';
    }

    function findStatus(form) {
        var key = form.getAttribute('data-autosave');

        if (!key) {
            return null;
        }

        return document.querySelector('[data-autosave-status="' + key + '"]');
    }

    function setStatus(statusEl, state, message) {
        if (!statusEl) {
            return;
        }

        statusEl.dataset.state = state;
        statusEl.textContent = message;
    }

    function bindForm(form) {
        var statusEl = findStatus(form);
        var timer = null;
        var dirty = false;
        var saving = false;
        var pending = false;

        setStatus(statusEl, 'idle', 'Changes save automatically');

        function save() {
            if (saving) {
                pending = true;

                return;
            }

            saving = true;
            setStatus(statusEl, 'saving', 'Saving…');

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Auto-Save': '1',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    return response.json()
                        .catch(function () {
                            return {};
                        })
                        .then(function (data) {
                            if (!response.ok) {
                                var message = data.message || 'Could not save changes.';

                                if (data.errors) {
                                    message = 'Could not save. Check entered values.';
                                }

                                throw new Error(message);
                            }

                            return data;
                        });
                })
                .then(function () {
                    dirty = false;
                    setStatus(statusEl, 'saved', 'All changes saved');

                    if (pending) {
                        pending = false;
                        save();
                    }
                })
                .catch(function (error) {
                    setStatus(statusEl, 'error', error.message || 'Could not save changes.');
                })
                .finally(function () {
                    saving = false;
                });
        }

        function scheduleSave() {
            dirty = true;
            setStatus(statusEl, 'pending', 'Unsaved changes…');
            window.clearTimeout(timer);
            timer = window.setTimeout(save, DEBOUNCE_MS);
        }

        form.addEventListener('input', scheduleSave);
        form.addEventListener('change', scheduleSave);

        form.addEventListener('submit', function () {
            window.clearTimeout(timer);
            dirty = false;
            setStatus(statusEl, 'saving', 'Saving…');
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden' && dirty) {
                window.clearTimeout(timer);
                save();
            }
        });

        window.addEventListener('beforeunload', function (event) {
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }

    function init() {
        document.querySelectorAll('form[data-autosave]').forEach(bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
