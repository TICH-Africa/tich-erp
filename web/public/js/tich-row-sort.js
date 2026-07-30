(function () {
    function getDragAfterElement(container, y) {
        var rows = Array.prototype.slice.call(
            container.querySelectorAll('tr[data-sortable-row]:not(.is-dragging)')
        );

        return rows.reduce(function (closest, child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    function collectOrder(tbody) {
        return Array.prototype.map.call(
            tbody.querySelectorAll('tr[data-sortable-row]'),
            function (row) {
                return row.getAttribute('data-sort-id');
            }
        );
    }

    function persistOrder(tbody) {
        var url = tbody.getAttribute('data-sort-url');

        if (! url) {
            return;
        }

        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var statusEl = document.querySelector(tbody.getAttribute('data-sort-status') || '');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': tokenMeta ? tokenMeta.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ order: collectOrder(tbody) }),
        }).then(function (response) {
            if (! response.ok) {
                throw new Error('Reorder failed');
            }

            if (statusEl) {
                statusEl.textContent = 'Order saved.';
                statusEl.style.color = 'var(--tich-green)';
                window.setTimeout(function () {
                    statusEl.textContent = '';
                }, 2000);
            }
        }).catch(function () {
            if (statusEl) {
                statusEl.textContent = 'Could not save order. Refresh and try again.';
                statusEl.style.color = '#c0392b';
            }
        });
    }

    function initSortableTable(tbody) {
        if (tbody.dataset.sortableInit === '1') {
            return;
        }

        tbody.dataset.sortableInit = '1';

        tbody.querySelectorAll('tr[data-sortable-row]').forEach(function (row) {
            var handle = row.querySelector('.tich-drag-handle');

            if (handle) {
                handle.addEventListener('mousedown', function () {
                    row.setAttribute('draggable', 'true');
                });
            } else {
                row.setAttribute('draggable', 'true');
            }

            row.addEventListener('dragstart', function () {
                row.classList.add('is-dragging');
            });

            row.addEventListener('dragend', function () {
                row.classList.remove('is-dragging');
                row.removeAttribute('draggable');
                persistOrder(tbody);
            });
        });

        tbody.addEventListener('dragover', function (event) {
            event.preventDefault();
            var dragging = tbody.querySelector('.is-dragging');

            if (! dragging) {
                return;
            }

            var afterElement = getDragAfterElement(tbody, event.clientY);

            if (afterElement == null) {
                tbody.appendChild(dragging);
            } else {
                tbody.insertBefore(dragging, afterElement);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-row-sortable]').forEach(initSortableTable);
    });
})();
