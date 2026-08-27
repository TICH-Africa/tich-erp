@props([
    'permissionMatrices',
    'categoryLabels',
])

@php
    $categoryOrder = array_keys($categoryLabels);
@endphp

<div
    id="role-permissions-modal"
    class="tich-modal"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="role-permissions-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="role-permissions-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 56rem; width: 95vw;">
        <header class="tich-modal__header">
            <h2 id="role-permissions-modal-title" class="tich-h3" style="margin: 0;">Permissions</h2>
            <button type="button" class="tich-modal__close" data-close-modal="role-permissions-modal" aria-label="Close">&times;</button>
        </header>
        <form id="role-permissions-form" method="POST" action="#" class="tich-modal__body">
            @csrf
            @method('PUT')
            <p id="role-permissions-help" class="tich-caption tich-mb-4">
                Toggle what this role can view, create, edit, approve, manage, and audit within its module scope.
            </p>
            <div id="role-permissions-matrix" style="overflow-x: auto;"></div>
            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="role-permissions-modal">Close</button>
                <button type="submit" id="role-permissions-save" class="tich-btn tich-btn-primary">Save permissions</button>
            </footer>
        </form>
    </div>
</div>

<script type="application/json" id="role-permission-matrices-data">@json($permissionMatrices)</script>
<script type="application/json" id="role-permission-category-order">@json($categoryOrder)</script>
<script type="application/json" id="role-permission-category-labels">@json($categoryLabels)</script>
<script>
(function () {
    var form = document.getElementById('role-permissions-form');
    var container = document.getElementById('role-permissions-matrix');
    var title = document.getElementById('role-permissions-modal-title');
    var help = document.getElementById('role-permissions-help');
    var saveBtn = document.getElementById('role-permissions-save');

    if (!form || !container) return;

    var matrices = JSON.parse(document.getElementById('role-permission-matrices-data').textContent || '{}');
    var categoryOrder = JSON.parse(document.getElementById('role-permission-category-order').textContent || '[]');
    var categoryLabels = JSON.parse(document.getElementById('role-permission-category-labels').textContent || '{}');
    var readOnly = false;

    function buildMatrix(roleId) {
        var rows = matrices[roleId] || [];
        container.innerHTML = '';

        if (!rows.length) {
            container.innerHTML = '<p class="tich-text">No permissions are available for this role scope.</p>';
            return;
        }

        var table = document.createElement('table');
        table.className = 'tich-admin-table';
        table.style.minWidth = '100%';

        var thead = document.createElement('thead');
        var headRow = document.createElement('tr');
        var actionTh = document.createElement('th');
        actionTh.textContent = 'Resource / action';
        headRow.appendChild(actionTh);

        categoryOrder.forEach(function (category) {
            var th = document.createElement('th');
            th.textContent = categoryLabels[category] || category;
            th.style.textAlign = 'center';
            th.style.whiteSpace = 'nowrap';
            headRow.appendChild(th);
        });

        thead.appendChild(headRow);
        table.appendChild(thead);

        var tbody = document.createElement('tbody');
        var lastModule = null;

        rows.forEach(function (row) {
            if (row.module && row.module !== lastModule) {
                lastModule = row.module;
                var moduleRow = document.createElement('tr');
                var moduleCell = document.createElement('td');
                moduleCell.colSpan = categoryOrder.length + 1;
                moduleCell.innerHTML = '<strong style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.04em; color: var(--tich-muted, #6b7280);">' + row.module + '</strong>';
                moduleRow.appendChild(moduleCell);
                tbody.appendChild(moduleRow);
            }

            var tr = document.createElement('tr');
            var labelTd = document.createElement('td');
            labelTd.textContent = row.label;
            tr.appendChild(labelTd);

            categoryOrder.forEach(function (category) {
                var td = document.createElement('td');
                td.style.textAlign = 'center';

                var cell = row.categories[category];
                if (cell) {
                    var label = document.createElement('label');
                    label.style.display = 'inline-flex';
                    label.title = cell.slug;
                    var input = document.createElement('input');
                    input.type = 'checkbox';
                    input.name = 'permission_ids[]';
                    input.value = cell.id;
                    input.checked = !!cell.checked;
                    input.disabled = readOnly;
                    label.appendChild(input);
                    td.appendChild(label);
                } else {
                    td.innerHTML = '<span class="tich-caption">-</span>';
                }

                tr.appendChild(td);
            });

            tbody.appendChild(tr);
        });

        table.appendChild(tbody);
        container.appendChild(table);
    }

    document.querySelectorAll('.role-permissions-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var roleId = trigger.getAttribute('data-role-id');
            var roleName = trigger.getAttribute('data-role-name') || 'Role';
            readOnly = trigger.getAttribute('data-readonly') === '1';
            form.action = readOnly ? '#' : (trigger.getAttribute('data-permissions-url') || '#');
            title.textContent = (readOnly ? 'Catalog permissions - ' : 'Permissions - ') + roleName;

            if (help) {
                help.textContent = readOnly
                    ? 'Predefined role permissions are hardcoded in config/tich-module-roles.php and cannot be edited here.'
                    : 'Toggle what this role can view, create, edit, approve, manage, and audit within its module scope.';
            }

            if (saveBtn) {
                saveBtn.hidden = readOnly;
                saveBtn.disabled = readOnly;
            }

            buildMatrix(roleId);
        });
    });

    form.addEventListener('submit', function (event) {
        if (readOnly) {
            event.preventDefault();
        }
    });
})();
</script>
