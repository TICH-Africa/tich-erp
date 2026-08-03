(function () {
    'use strict';

    var dataEl = document.getElementById('payroll-tax-settings-data');
    var form = document.getElementById('tax-settings-form');

    if (!dataEl || !form) {
        return;
    }

    var boot = JSON.parse(dataEl.textContent || '{}');
    var state = {
        deductionTypes: boot.deductionTypes || [],
        bands: boot.bands || [],
    };

    var nextTempTypeKey = 1;
    var nextTempBandKey = 1;

    var hiddenFields = document.getElementById('tax-settings-hidden-fields');
    var itemsTbody = document.getElementById('deduction-items-tbody');
    var bandsTbody = document.getElementById('bands-tbody');
    var bandsHeaderRow = document.getElementById('bands-header-row');
    var bandsFooterRow = document.getElementById('cumulative-footer-row');
    var bandOrderStatus = document.getElementById('band-order-status');
    var itemOrderStatus = document.getElementById('item-order-status');

    function parseNum(value) {
        var n = parseFloat(value);
        return Number.isFinite(n) ? n : 0;
    }

    function formatNum(value) {
        return parseNum(value).toFixed(2);
    }

    function round2(value) {
        return Math.round(parseNum(value) * 100) / 100;
    }

    function matrixColumns() {
        return state.deductionTypes.filter(function (type) {
            return type.value_type === 'band_percent' && type.is_active;
        });
    }

    function typeKey(type) {
        return type.id != null ? String(type.id) : type._tempKey;
    }

    function bandKey(band) {
        return band.id != null ? String(band.id) : band._tempKey;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showStatus(el, message, isError) {
        if (!el) {
            return;
        }

        el.textContent = message;
        el.style.color = isError ? '#c0392b' : 'var(--tich-green, #16a34a)';

        if (message) {
            window.setTimeout(function () {
                if (el.textContent === message) {
                    el.textContent = '';
                }
            }, 2500);
        }
    }

    function getDragAfterElement(container, y, selector) {
        var rows = Array.prototype.slice.call(container.querySelectorAll(selector + ':not(.is-dragging)'));

        return rows.reduce(function (closest, child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    function bandsSortedByMin() {
        return state.bands.slice().sort(function (a, b) {
            return parseNum(a.min_amount) - parseNum(b.min_amount);
        });
    }

    function bandsAreOrderedByMin() {
        var mins = state.bands.map(function (band) {
            return parseNum(band.min_amount);
        });

        for (var i = 1; i < mins.length; i++) {
            if (mins[i] < mins[i - 1]) {
                return false;
            }
        }

        return true;
    }

    function syncBandDisplayOrder() {
        state.bands.forEach(function (band, index) {
            band.display_order = index;
        });
    }

    function syncTypeDisplayOrder() {
        state.deductionTypes.forEach(function (type, index) {
            type.display_order = index;
        });
    }

    function cumulativeForBand(band) {
        var total = parseNum(band.rate_percent);

        matrixColumns().forEach(function (type) {
            total += parseNum(band.deductions[typeKey(type)]);
        });

        return total;
    }

    function renderDeductionItems() {
        if (!itemsTbody) {
            return;
        }

        if (!state.deductionTypes.length) {
            itemsTbody.innerHTML = '<tr><td colspan="6" class="tich-table-empty">No tax items yet. Add NSSF, SHA/SHIF, personal relief, etc.</td></tr>';
            return;
        }

        itemsTbody.innerHTML = state.deductionTypes.map(function (type, index) {
            var details = type.value_type === 'global_fixed'
                ? 'Fixed KES ' + formatNum(type.fixed_amount)
                : 'Per-band %';

            if (type.value_type === 'band_percent' && type.employer_rate_percent) {
                details += ' · Employer ' + formatNum(type.employer_rate_percent) + '%';
            }

            if (type.reduces_taxable) {
                details += ' · Reduces taxable';
            }

            return `
                <tr data-sortable-item data-item-key="${escapeHtml(typeKey(type))}">
                    <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                    <td><strong>${escapeHtml(type.label)}</strong></td>
                    <td>${type.value_type === 'global_fixed' ? 'Fixed amount' : 'Band percentage'}</td>
                    <td class="tich-caption">${escapeHtml(details)}</td>
                    <td>${type.is_active ? 'Active' : 'Inactive'}</td>
                    <td style="white-space: nowrap;">
                        <button type="button" class="tich-btn tich-btn-ghost edit-deduction-item" data-item-key="${escapeHtml(typeKey(type))}">Edit</button>
                    </td>
                </tr>
            `;
        }).join('');

        initItemSortable();
    }

    function renderBandsTable() {
        var columns = matrixColumns();

        if (bandsHeaderRow) {
            var staticHeaders = `
                <th style="width: 2.5rem;"></th>
                <th>Label</th>
                <th>Min (KES)</th>
                <th>Max (KES)</th>
                <th>PAYE %</th>
            `;

            var columnHeaders = columns.map(function (type) {
                return `<th>${escapeHtml(type.label)} %</th>`;
            }).join('');

            bandsHeaderRow.innerHTML = staticHeaders + columnHeaders + `
                <th>Cumulative %</th>
                <th>Active</th>
                <th style="width: 6rem;"></th>
            `;
        }

        if (!bandsTbody) {
            return;
        }

        if (!state.bands.length) {
            bandsTbody.innerHTML = '<tr><td colspan="20" class="tich-table-empty">No PAYE bands yet.</td></tr>';
            renderBandsFooter(columns);
            return;
        }

        bandsTbody.innerHTML = state.bands.map(function (band) {
            var columnCells = columns.map(function (type) {
                var rate = band.deductions[typeKey(type)];
                return `<td>${rate == null || rate === '' ? '-' : formatNum(rate)}</td>`;
            }).join('');

            return `
                <tr data-sortable-band data-band-key="${escapeHtml(bandKey(band))}">
                    <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                    <td><strong>${escapeHtml(band.label)}</strong></td>
                    <td>${formatNum(band.min_amount)}</td>
                    <td>${band.max_amount == null || band.max_amount === '' ? 'No limit' : formatNum(band.max_amount)}</td>
                    <td>${formatNum(band.rate_percent)}</td>
                    ${columnCells}
                    <td class="tich-caption cumulative-cell">${formatNum(cumulativeForBand(band))}</td>
                    <td>${band.is_active ? 'Active' : 'Inactive'}</td>
                    <td><button type="button" class="tich-btn tich-btn-ghost edit-band" data-band-key="${escapeHtml(bandKey(band))}">Edit</button></td>
                </tr>
            `;
        }).join('');

        renderBandsFooter(columns);
        initBandSortable();
    }

    function renderBandsFooter(columns) {
        if (!bandsFooterRow) {
            return;
        }

        var columnFooters = columns.map(function (type) {
            var sum = 0;

            state.bands.forEach(function (band) {
                sum += parseNum(band.deductions[typeKey(type)]);
            });

            return `<td class="tich-caption">${formatNum(sum)}</td>`;
        }).join('');

        var payeSum = 0;
        var deductionSum = 0;

        state.bands.forEach(function (band) {
            payeSum += parseNum(band.rate_percent);
            matrixColumns().forEach(function (type) {
                deductionSum += parseNum(band.deductions[typeKey(type)]);
            });
        });

        bandsFooterRow.innerHTML = `
            <td></td>
            <td colspan="4"><strong>Cumulative (sum of rates)</strong></td>
            ${columnFooters}
            <td class="tich-caption" id="footer-cumulative-total">${formatNum(payeSum + deductionSum)}</td>
            <td colspan="2"></td>
        `;
    }

    function renderAll() {
        renderDeductionItems();
        renderBandsTable();
        serializeHiddenFields();
    }

    function appendHidden(name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value == null ? '' : value;
        hiddenFields.appendChild(input);
    }

    function serializeHiddenFields() {
        if (!hiddenFields) {
            return;
        }

        hiddenFields.innerHTML = '';

        state.deductionTypes.forEach(function (type, index) {
            if (type.id != null) {
                appendHidden('deduction_types[' + index + '][id]', type.id);
            }

            appendHidden('deduction_types[' + index + '][label]', type.label);
            appendHidden('deduction_types[' + index + '][value_type]', type.value_type);
            appendHidden('deduction_types[' + index + '][display_order]', index);
            appendHidden('deduction_types[' + index + '][is_active]', type.is_active ? '1' : '0');

            if (type.value_type === 'global_fixed') {
                appendHidden('deduction_types[' + index + '][fixed_amount]', type.fixed_amount);
            }

            if (type.employer_rate_percent != null && type.employer_rate_percent !== '') {
                appendHidden('deduction_types[' + index + '][employer_rate_percent]', type.employer_rate_percent);
            }

            if (type.reduces_taxable) {
                appendHidden('deduction_types[' + index + '][reduces_taxable]', '1');
            }
        });

        state.bands.forEach(function (band, index) {
            if (band.id != null) {
                appendHidden('bands[' + index + '][id]', band.id);
            }

            appendHidden('bands[' + index + '][label]', band.label);
            appendHidden('bands[' + index + '][min_amount]', band.min_amount);
            appendHidden('bands[' + index + '][max_amount]', band.max_amount == null ? '' : band.max_amount);
            appendHidden('bands[' + index + '][rate_percent]', band.rate_percent);
            appendHidden('bands[' + index + '][display_order]', index);
            appendHidden('bands[' + index + '][is_active]', band.is_active ? '1' : '0');

            matrixColumns().forEach(function (type) {
                var rate = band.deductions[typeKey(type)];

                if (rate == null || rate === '') {
                    return;
                }

                var typeIndex = state.deductionTypes.indexOf(type);
                var deductionKey = type.id != null ? type.id : 'new_' + typeIndex;
                appendHidden('bands[' + index + '][deductions][' + deductionKey + ']', rate);
            });
        });
    }

    function findType(key) {
        return state.deductionTypes.find(function (type) {
            return typeKey(type) === String(key);
        }) || null;
    }

    function findBand(key) {
        return state.bands.find(function (band) {
            return bandKey(band) === String(key);
        }) || null;
    }

    function bindSortableRows(tbody, rowSelector, onReorder, statusEl) {
        if (!tbody) {
            return;
        }

        tbody.querySelectorAll(rowSelector).forEach(function (row) {
            if (row.dataset.sortBound === '1') {
                return;
            }

            row.dataset.sortBound = '1';
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
                onReorder(statusEl);
            });
        });

        if (tbody.dataset.sortDragoverBound === '1') {
            return;
        }

        tbody.dataset.sortDragoverBound = '1';

        tbody.addEventListener('dragover', function (event) {
            event.preventDefault();
            var dragging = tbody.querySelector('.is-dragging');

            if (!dragging) {
                return;
            }

            var afterElement = getDragAfterElement(tbody, event.clientY, rowSelector);

            if (afterElement == null) {
                tbody.appendChild(dragging);
            } else {
                tbody.insertBefore(dragging, afterElement);
            }
        });
    }

    function initItemSortable() {
        bindSortableRows(itemsTbody, 'tr[data-sortable-item]', function (statusEl) {
            var keys = Array.prototype.map.call(
                itemsTbody.querySelectorAll('tr[data-sortable-item]'),
                function (row) {
                    return row.getAttribute('data-item-key');
                }
            );

            var reordered = keys.map(function (key) {
                return findType(key);
            }).filter(Boolean);

            state.deductionTypes = reordered;
            syncTypeDisplayOrder();
            renderBandsTable();
            serializeHiddenFields();
            showStatus(statusEl, 'Item order updated.');
        }, itemOrderStatus);
    }

    function initBandSortable() {
        bindSortableRows(bandsTbody, 'tr[data-sortable-band]', function (statusEl) {
            var keys = Array.prototype.map.call(
                bandsTbody.querySelectorAll('tr[data-sortable-band]'),
                function (row) {
                    return row.getAttribute('data-band-key');
                }
            );

            var reordered = keys.map(function (key) {
                return findBand(key);
            }).filter(Boolean);

            state.bands = reordered;

            if (!bandsAreOrderedByMin()) {
                state.bands = bandsSortedByMin();
                renderBandsTable();
                showStatus(statusEl, 'Bands must stay ordered from lowest to highest bracket. Reverted.', true);
                return;
            }

            syncBandDisplayOrder();
            renderBandsTable();
            serializeHiddenFields();
            showStatus(statusEl, 'Band order updated.');
        }, bandOrderStatus);
    }

    function setModalField(id, value) {
        var field = document.getElementById(id);

        if (!field) {
            return;
        }

        if (field.type === 'checkbox') {
            field.checked = !!value;
        } else {
            field.value = value == null ? '' : value;
        }
    }

    function readModalField(id) {
        var field = document.getElementById(id);

        if (!field) {
            return '';
        }

        if (field.type === 'checkbox') {
            return field.checked;
        }

        return field.value;
    }

    function toggleDeductionTypeFields() {
        var valueType = readModalField('deduction-item-value-type');
        var fixedGroup = document.getElementById('deduction-item-fixed-group');
        var bandFields = document.getElementById('deduction-item-band-fields');

        if (fixedGroup) {
            fixedGroup.hidden = valueType !== 'global_fixed';
        }

        if (bandFields) {
            bandFields.hidden = valueType !== 'band_percent';
        }
    }

    function openDeductionItemModal(key) {
        var modal = document.getElementById('deduction-item-modal');
        var deleteBtn = document.getElementById('deduction-item-delete-btn');
        var isNew = !key;
        var type = isNew ? {
            label: '',
            value_type: 'band_percent',
            fixed_amount: '',
            employer_rate_percent: '',
            reduces_taxable: false,
            is_active: true,
        } : findType(key);

        if (!type) {
            return;
        }

        modal.dataset.editKey = isNew ? '' : key;
        document.getElementById('deduction-item-modal-title').textContent = isNew ? 'Add tax item' : 'Edit tax item';
        setModalField('deduction-item-label', type.label);
        setModalField('deduction-item-value-type', type.value_type);
        setModalField('deduction-item-fixed-amount', type.fixed_amount === '' || type.fixed_amount == null ? '' : formatNum(type.fixed_amount));
        setModalField('deduction-item-employer-rate', type.employer_rate_percent === '' || type.employer_rate_percent == null ? '' : formatNum(type.employer_rate_percent));
        setModalField('deduction-item-reduces-taxable', type.reduces_taxable);
        setModalField('deduction-item-active', type.is_active);
        deleteBtn.hidden = isNew;
        toggleDeductionTypeFields();

        if (window.tichOpenModal) {
            window.tichOpenModal('deduction-item-modal');
        }
    }

    function saveDeductionItemFromModal() {
        var modal = document.getElementById('deduction-item-modal');
        var key = modal.dataset.editKey || '';
        var isNew = !key;
        var label = readModalField('deduction-item-label').trim();

        if (!label) {
            showStatus(itemOrderStatus, 'Label is required.', true);
            return;
        }

        var valueType = readModalField('deduction-item-value-type');
        var payload = {
            label: label,
            value_type: valueType,
            fixed_amount: valueType === 'global_fixed' && readModalField('deduction-item-fixed-amount') !== ''
                ? round2(readModalField('deduction-item-fixed-amount'))
                : '',
            employer_rate_percent: readModalField('deduction-item-employer-rate') === '' ? '' : round2(readModalField('deduction-item-employer-rate')),
            reduces_taxable: readModalField('deduction-item-reduces-taxable'),
            is_active: readModalField('deduction-item-active'),
        };

        if (isNew) {
            payload._tempKey = 'new_type_' + (nextTempTypeKey++);
            state.deductionTypes.push(payload);
        } else {
            var existing = findType(key);

            if (!existing) {
                return;
            }

            if (existing.value_type === 'band_percent' && payload.value_type === 'global_fixed') {
                state.bands.forEach(function (band) {
                    delete band.deductions[key];
                });
            }

            Object.assign(existing, payload);
        }

        syncTypeDisplayOrder();
        renderAll();

        if (window.tichCloseModal) {
            window.tichCloseModal('deduction-item-modal');
        }
    }

    function deleteDeductionItemFromModal() {
        var modal = document.getElementById('deduction-item-modal');
        var key = modal.dataset.editKey || '';
        var type = findType(key);

        if (!type || !window.confirm('Delete tax item "' + type.label + '"?')) {
            return;
        }

        state.deductionTypes = state.deductionTypes.filter(function (item) {
            return typeKey(item) !== key;
        });

        state.bands.forEach(function (band) {
            delete band.deductions[key];
        });

        renderAll();

        if (window.tichCloseModal) {
            window.tichCloseModal('deduction-item-modal');
        }
    }

    function renderBandDeductionFields(band) {
        var container = document.getElementById('band-deduction-fields');

        if (!container) {
            return;
        }

        var columns = matrixColumns();

        if (!columns.length) {
            container.innerHTML = '<p class="tich-caption">Add band-percentage tax items (NSSF, SHA/SHIF, etc.) to set rates per bracket.</p>';
            return;
        }

        container.innerHTML = columns.map(function (type) {
            var rate = band ? band.deductions[typeKey(type)] : '';

            return `
                <div class="tich-form-group">
                    <label class="tich-label">${escapeHtml(type.label)} (%)</label>
                    <input type="number" class="tich-input band-modal-deduction" data-type-key="${escapeHtml(typeKey(type))}" min="0" max="100" step="0.01" value="${rate == null ? '' : formatNum(rate)}">
                </div>
            `;
        }).join('');
    }

    function openBandModal(key) {
        var modal = document.getElementById('band-modal');
        var deleteBtn = document.getElementById('band-delete-btn');
        var isNew = !key;
        var band = isNew ? {
            label: '',
            min_amount: '',
            max_amount: '',
            rate_percent: '',
            deductions: {},
            is_active: true,
        } : findBand(key);

        if (!band) {
            return;
        }

        modal.dataset.editKey = isNew ? '' : key;
        document.getElementById('band-modal-title').textContent = isNew ? 'Add PAYE band' : 'Edit PAYE band';
        setModalField('band-label', band.label);
        setModalField('band-min-amount', band.min_amount === '' || band.min_amount == null ? '' : formatNum(band.min_amount));
        setModalField('band-max-amount', band.max_amount === '' || band.max_amount == null ? '' : formatNum(band.max_amount));
        setModalField('band-rate-percent', band.rate_percent === '' || band.rate_percent == null ? '' : formatNum(band.rate_percent));
        setModalField('band-active', band.is_active);
        deleteBtn.hidden = isNew;
        renderBandDeductionFields(band);

        if (window.tichOpenModal) {
            window.tichOpenModal('band-modal');
        }
    }

    function saveBandFromModal() {
        var modal = document.getElementById('band-modal');
        var key = modal.dataset.editKey || '';
        var isNew = !key;
        var label = readModalField('band-label').trim();

        if (!label) {
            showStatus(bandOrderStatus, 'Band label is required.', true);
            return;
        }

        var payload = {
            label: label,
            min_amount: round2(readModalField('band-min-amount')),
            max_amount: readModalField('band-max-amount') === '' ? '' : round2(readModalField('band-max-amount')),
            rate_percent: round2(readModalField('band-rate-percent')),
            is_active: readModalField('band-active'),
            deductions: {},
        };

        document.querySelectorAll('.band-modal-deduction').forEach(function (input) {
            var typeKeyValue = input.getAttribute('data-type-key');
            payload.deductions[typeKeyValue] = input.value === '' ? '' : round2(input.value);
        });

        if (isNew) {
            payload._tempKey = 'new_band_' + (nextTempBandKey++);
            state.bands.push(payload);
        } else {
            var existing = findBand(key);

            if (!existing) {
                return;
            }

            payload.deductions = Object.assign({}, existing.deductions, payload.deductions);
            Object.assign(existing, payload);
        }

        if (!bandsAreOrderedByMin()) {
            state.bands = bandsSortedByMin();
            showStatus(bandOrderStatus, 'Bands were reordered lowest to highest by min amount.', false);
        }

        syncBandDisplayOrder();
        renderAll();

        if (window.tichCloseModal) {
            window.tichCloseModal('band-modal');
        }
    }

    function deleteBandFromModal() {
        var modal = document.getElementById('band-modal');
        var key = modal.dataset.editKey || '';
        var band = findBand(key);

        if (!band || !window.confirm('Delete PAYE band "' + band.label + '"?')) {
            return;
        }

        state.bands = state.bands.filter(function (item) {
            return bandKey(item) !== key;
        });

        syncBandDisplayOrder();
        renderAll();

        if (window.tichCloseModal) {
            window.tichCloseModal('band-modal');
        }
    }

    document.getElementById('add-deduction-item-btn')?.addEventListener('click', function () {
        openDeductionItemModal(null);
    });

    document.getElementById('add-band-btn')?.addEventListener('click', function () {
        openBandModal(null);
    });

    document.getElementById('deduction-item-save-btn')?.addEventListener('click', saveDeductionItemFromModal);
    document.getElementById('deduction-item-delete-btn')?.addEventListener('click', deleteDeductionItemFromModal);
    document.getElementById('band-save-btn')?.addEventListener('click', saveBandFromModal);
    document.getElementById('band-delete-btn')?.addEventListener('click', deleteBandFromModal);
    document.getElementById('deduction-item-value-type')?.addEventListener('change', toggleDeductionTypeFields);

    document.addEventListener('click', function (event) {
        var editItem = event.target.closest('.edit-deduction-item');

        if (editItem) {
            openDeductionItemModal(editItem.getAttribute('data-item-key'));
            return;
        }

        var editBand = event.target.closest('.edit-band');

        if (editBand) {
            openBandModal(editBand.getAttribute('data-band-key'));
        }
    });

    form.addEventListener('submit', function (event) {
        serializeHiddenFields();

        if (!state.bands.length) {
            event.preventDefault();
            showStatus(bandOrderStatus, 'Add at least one PAYE band before saving.', true);
            return;
        }

        if (!bandsAreOrderedByMin()) {
            event.preventDefault();
            showStatus(bandOrderStatus, 'PAYE bands must be ordered from lowest to highest income bracket.', true);
        }
    });

    state.bands = bandsSortedByMin();
    syncBandDisplayOrder();
    syncTypeDisplayOrder();
    renderAll();
})();
