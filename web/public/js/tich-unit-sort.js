(function () {
    function updateRowOrder(tbody) {
        tbody.querySelectorAll('tr[data-sortable-row]').forEach(function (row, index) {
            var order = index + 1;
            row.querySelectorAll('[data-sort-order-field]').forEach(function (input) {
                input.value = order;
            });
        });
    }

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
                updateRowOrder(tbody);
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

        updateRowOrder(tbody);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-unit-sortable]').forEach(initSortableTable);
    });
})();
