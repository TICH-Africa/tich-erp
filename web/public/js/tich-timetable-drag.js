(function () {
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function moveUrl(baseUrl, sessionId) {
        return baseUrl.replace('__SESSION__', String(sessionId));
    }

    function isElement(node) {
        return node && node.nodeType === 1;
    }

    function setStatus(wrap, message, type) {
        var status = wrap.querySelector('.tich-timetable-drag-status');
        if (!status) {
            return;
        }

        status.hidden = !message;
        status.textContent = message || '';
        status.classList.toggle('is-error', type === 'error');
        status.classList.toggle('is-success', type === 'success');
    }

    function ensureEmptyPlaceholder(cell) {
        if (!isElement(cell)) {
            return;
        }

        if (cell.querySelector('.tich-timetable-session')) {
            var empty = cell.querySelector('.tich-timetable-grid__empty');
            if (empty) {
                empty.remove();
            }
            return;
        }

        if (!cell.querySelector('.tich-timetable-grid__empty')) {
            var placeholder = document.createElement('span');
            placeholder.className = 'tich-timetable-grid__empty';
            placeholder.textContent = '—';
            cell.appendChild(placeholder);
        }
    }

    function initEditableGrid(wrap) {
        if (wrap.dataset.dragInit === '1') {
            return;
        }

        wrap.dataset.dragInit = '1';

        var baseMoveUrl = wrap.dataset.moveUrl;
        var draggedSession = null;
        var draggedFromCell = null;

        wrap.addEventListener('dragstart', function (event) {
            var session = event.target.closest('.tich-timetable-session.is-draggable');
            if (!session || !wrap.contains(session)) {
                return;
            }

            draggedSession = session;
            draggedFromCell = session.closest('.tich-timetable-grid__dropzone');
            session.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', session.dataset.sessionId || '');
            setStatus(wrap, '', '');
        });

        wrap.addEventListener('dragend', function (event) {
            var session = event.target.closest('.tich-timetable-session.is-draggable');
            if (session) {
                session.classList.remove('is-dragging');
            }

            wrap.querySelectorAll('.tich-timetable-grid__dropzone.is-drag-over').forEach(function (cell) {
                cell.classList.remove('is-drag-over');
            });

            draggedSession = null;
            draggedFromCell = null;
        });

        wrap.addEventListener('dragover', function (event) {
            var cell = event.target.closest('.tich-timetable-grid__dropzone');
            if (!cell || !wrap.contains(cell) || !draggedSession) {
                return;
            }

            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            wrap.querySelectorAll('.tich-timetable-grid__dropzone.is-drag-over').forEach(function (activeCell) {
                if (activeCell !== cell) {
                    activeCell.classList.remove('is-drag-over');
                }
            });

            cell.classList.add('is-drag-over');
        });

        wrap.addEventListener('dragleave', function (event) {
            var cell = event.target.closest('.tich-timetable-grid__dropzone');
            if (!cell || !wrap.contains(cell)) {
                return;
            }

            if (!cell.contains(event.relatedTarget)) {
                cell.classList.remove('is-drag-over');
            }
        });

        wrap.addEventListener('drop', function (event) {
            var cell = event.target.closest('.tich-timetable-grid__dropzone');
            if (!cell || !wrap.contains(cell)) {
                return;
            }

            event.preventDefault();
            cell.classList.remove('is-drag-over');

            var sessionEl = draggedSession;
            if (!isElement(sessionEl)) {
                var sessionIdFromTransfer = event.dataTransfer.getData('text/plain');
                if (sessionIdFromTransfer) {
                    sessionEl = wrap.querySelector('[data-session-id="' + sessionIdFromTransfer + '"]');
                }
            }

            if (!isElement(sessionEl)) {
                return;
            }

            var fromCell = draggedFromCell || sessionEl.closest('.tich-timetable-grid__dropzone');

            if (cell === fromCell) {
                return;
            }

            var sessionId = sessionEl.dataset.sessionId;
            var dayOfWeek = cell.dataset.dropDay;
            var segmentId = cell.dataset.segmentId;
            var swapEl = cell.querySelector('.tich-timetable-session.is-draggable:not(.is-dragging)');
            var swapSessionId = swapEl ? swapEl.dataset.sessionId : null;

            setStatus(wrap, 'Saving…', 'success');
            wrap.classList.add('is-saving');

            fetch(moveUrl(baseMoveUrl, sessionId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    day_of_week: Number(dayOfWeek),
                    segment_id: Number(segmentId),
                    swap_session_id: swapSessionId ? Number(swapSessionId) : null,
                }),
            })
                .then(function (response) {
                    var contentType = response.headers.get('content-type') || '';

                    if (contentType.indexOf('application/json') !== -1) {
                        return response.json().then(function (payload) {
                            return { ok: response.ok, payload: payload };
                        });
                    }

                    return { ok: false, payload: { message: 'Could not move session.' } };
                })
                .then(function (result) {
                    if (!result.ok) {
                        var message = result.payload.message
                            || (result.payload.errors && result.payload.errors.session && result.payload.errors.session[0])
                            || 'Could not move session.';
                        throw new Error(message);
                    }

                    if (isElement(swapEl) && isElement(fromCell)) {
                        fromCell.appendChild(swapEl);
                    }

                    if (isElement(sessionEl)) {
                        cell.appendChild(sessionEl);
                        sessionEl.classList.remove('is-dragging');
                    }

                    ensureEmptyPlaceholder(cell);
                    ensureEmptyPlaceholder(fromCell);
                    setStatus(wrap, result.payload.message || 'Session moved.', 'success');
                })
                .catch(function (error) {
                    setStatus(wrap, error.message || 'Could not move session.', 'error');
                })
                .finally(function () {
                    wrap.classList.remove('is-saving');
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-timetable-editable="1"]').forEach(initEditableGrid);
    });
})();
