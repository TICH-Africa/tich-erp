(function () {
    'use strict';

    var CHEVRON = '<svg class="tich-dropdown__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';
    var CHECK = '<svg class="tich-dropdown__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';

    var openDropdown = null;

    function shouldEnhance(select) {
        if (!(select instanceof HTMLSelectElement)) {
            return false;
        }

        if (select.multiple || select.dataset.tichDropdown === 'skip') {
            return false;
        }

        return select.classList.contains('tich-input') || select.classList.contains('tich-select');
    }

    function selectedMeta(select) {
        var option = select.options[select.selectedIndex];

        if (!option) {
            return { text: '', placeholder: true };
        }

        var isPlaceholder = option.disabled || option.value === '';

        return {
            text: option.textContent.trim(),
            placeholder: isPlaceholder,
        };
    }

    function syncTrigger(select, labelEl) {
        var meta = selectedMeta(select);

        labelEl.textContent = meta.text || 'Choose an option…';
        labelEl.classList.toggle('is-placeholder', meta.placeholder);
    }

    function buildMenu(select, menu) {
        menu.innerHTML = '';

        var enableSearch = select.dataset.tichSearch === 'true' || select.options.length > 8;
        var list = document.createElement('div');
        list.className = 'tich-dropdown__options';
        list.setAttribute('role', 'presentation');

        if (enableSearch) {
            var searchWrap = document.createElement('div');
            searchWrap.className = 'tich-dropdown__search';

            var searchInput = document.createElement('input');
            searchInput.type = 'search';
            searchInput.className = 'tich-input tich-dropdown__search-input';
            searchInput.placeholder = select.dataset.tichSearchPlaceholder || 'Search…';
            searchInput.setAttribute('aria-label', 'Search options');
            searchInput.autocomplete = 'off';

            searchWrap.appendChild(searchInput);
            menu.appendChild(searchWrap);

            searchInput.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();
                    closeDropdown(select.tichDropdownWrapper);
                    if (select.tichDropdownTrigger) {
                        select.tichDropdownTrigger.focus();
                    }
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    var first = list.querySelector('.tich-dropdown__option:not(.is-disabled):not([hidden])');
                    if (first) {
                        first.focus();
                    }
                }
            });

            searchInput.addEventListener('input', function () {
                filterMenuOptions(list, searchInput.value);
            });

            menu._tichSearchInput = searchInput;
        }

        Array.from(select.children).forEach(function (node) {
            if (node.tagName === 'OPTGROUP') {
                var groupLabel = document.createElement('div');
                groupLabel.className = 'tich-dropdown__group-label';
                groupLabel.textContent = node.label;
                list.appendChild(groupLabel);

                Array.from(node.children).forEach(function (option) {
                    list.appendChild(createOptionButton(select, option));
                });

                return;
            }

            if (node.tagName === 'OPTION') {
                list.appendChild(createOptionButton(select, node));
            }
        });

        menu.appendChild(list);
        menu._tichOptionsList = list;
    }

    function filterMenuOptions(list, query) {
        var needle = (query || '').trim().toLowerCase();
        var visibleCount = 0;

        list.querySelectorAll('.tich-dropdown__option').forEach(function (button) {
            var haystack = (button.dataset.searchText || button.textContent || '').toLowerCase();
            var match = needle === '' || haystack.indexOf(needle) !== -1;
            button.hidden = !match;
            if (match) {
                visibleCount += 1;
            }
        });

        list.querySelectorAll('.tich-dropdown__group-label').forEach(function (label) {
            var next = label.nextElementSibling;
            var hasVisible = false;
            while (next && !next.classList.contains('tich-dropdown__group-label')) {
                if (next.classList.contains('tich-dropdown__option') && !next.hidden) {
                    hasVisible = true;
                    break;
                }
                next = next.nextElementSibling;
            }
            label.hidden = !hasVisible;
        });

        var empty = list.querySelector('.tich-dropdown__empty');
        if (visibleCount === 0) {
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'tich-dropdown__empty';
                empty.textContent = 'No matches';
                list.appendChild(empty);
            }
            empty.hidden = false;
        } else if (empty) {
            empty.hidden = true;
        }
    }

    function createOptionButton(select, option) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'tich-dropdown__option';
        button.dataset.value = option.value;
        button.dataset.searchText = option.textContent.trim();
        button.setAttribute('role', 'option');

        if (option.disabled) {
            button.classList.add('is-disabled');
            button.disabled = true;
        }

        if (option.selected && option.value !== '') {
            button.classList.add('is-selected');
            button.setAttribute('aria-selected', 'true');
        } else {
            button.setAttribute('aria-selected', 'false');
        }

        var text = document.createElement('span');
        text.className = 'tich-dropdown__option-text';
        text.textContent = option.textContent.trim();

        button.appendChild(text);
        button.insertAdjacentHTML('beforeend', CHECK);

        button.addEventListener('click', function () {
            if (option.disabled) {
                return;
            }

            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            closeDropdown(select.tichDropdownWrapper);
            syncTrigger(select, select.tichDropdownLabel);
            refreshSelectedStates(menuForSelect(select));

            if (select.tichDropdownTrigger) {
                select.tichDropdownTrigger.focus();
            }
        });

        return button;
    }

    function menuForSelect(select) {
        return select.tichDropdownWrapper ? select.tichDropdownWrapper.querySelector('.tich-dropdown__menu') : null;
    }

    function refreshSelectedStates(menu) {
        if (!menu) {
            return;
        }

        var select = menu.closest('.tich-dropdown').querySelector('select');
        var activeValue = select.value;

        menu.querySelectorAll('.tich-dropdown__option').forEach(function (button) {
            var isActive = button.dataset.value === activeValue && !button.classList.contains('is-disabled');
            button.classList.toggle('is-selected', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function closeDropdown(wrapper) {
        if (!wrapper) {
            return;
        }

        wrapper.classList.remove('is-open');
        wrapper.querySelector('.tich-dropdown__menu').hidden = true;
        wrapper.querySelector('.tich-dropdown__trigger').setAttribute('aria-expanded', 'false');

        if (openDropdown === wrapper) {
            openDropdown = null;
        }
    }

    function openMenu(wrapper) {
        if (openDropdown && openDropdown !== wrapper) {
            closeDropdown(openDropdown);
        }

        wrapper.classList.add('is-open');
        var menu = wrapper.querySelector('.tich-dropdown__menu');
        menu.hidden = false;
        wrapper.querySelector('.tich-dropdown__trigger').setAttribute('aria-expanded', 'true');
        openDropdown = wrapper;

        if (menu._tichSearchInput) {
            menu._tichSearchInput.value = '';
            filterMenuOptions(menu._tichOptionsList || menu, '');
            menu._tichSearchInput.focus();
            return;
        }

        var selected = wrapper.querySelector('.tich-dropdown__option.is-selected');
        if (selected) {
            selected.focus();
        }
    }

    function enhanceSelect(select) {
        if (select.dataset.tichDropdownEnhanced === 'true' || !shouldEnhance(select)) {
            return;
        }

        select.dataset.tichDropdownEnhanced = 'true';
        select.classList.add('tich-dropdown__native');

        var wrapper = document.createElement('div');
        wrapper.className = 'tich-dropdown';

        if (select.disabled) {
            wrapper.classList.add('is-disabled');
        }

        if (select.classList.contains('tich-input--error')) {
            wrapper.classList.add('is-error');
        }

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'tich-dropdown__trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        var label = document.createElement('span');
        label.className = 'tich-dropdown__label';

        trigger.appendChild(label);
        trigger.insertAdjacentHTML('beforeend', CHEVRON);

        var menu = document.createElement('div');
        menu.className = 'tich-dropdown__menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;

        var parent = select.parentNode;
        parent.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);

        select.tichDropdownWrapper = wrapper;
        select.tichDropdownLabel = label;
        select.tichDropdownTrigger = trigger;

        buildMenu(select, menu);
        syncTrigger(select, label);

        trigger.addEventListener('click', function () {
            if (select.disabled) {
                return;
            }

            if (wrapper.classList.contains('is-open')) {
                closeDropdown(wrapper);
            } else {
                buildMenu(select, menu);
                refreshSelectedStates(menu);
                openMenu(wrapper);
            }
        });

        trigger.addEventListener('keydown', function (event) {
            if (select.disabled) {
                return;
            }

            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                buildMenu(select, menu);
                refreshSelectedStates(menu);
                openMenu(wrapper);
            }
        });

        menu.addEventListener('keydown', function (event) {
            var options = Array.from(menu.querySelectorAll('.tich-dropdown__option:not(.is-disabled):not([hidden])'));
            var index = options.indexOf(document.activeElement);

            if (event.key === 'Escape') {
                event.preventDefault();
                closeDropdown(wrapper);
                trigger.focus();
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                var next = options[Math.min(index + 1, options.length - 1)] || options[0];
                if (next) {
                    next.focus();
                }
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index <= 0 && menu._tichSearchInput) {
                    menu._tichSearchInput.focus();
                    return;
                }
                var prev = options[Math.max(index - 1, 0)] || options[options.length - 1];
                if (prev) {
                    prev.focus();
                }
            }

            if (event.key === 'Enter' || event.key === ' ') {
                if (document.activeElement && document.activeElement.classList.contains('tich-dropdown__option')) {
                    event.preventDefault();
                    document.activeElement.click();
                }
            }
        });

        select.addEventListener('change', function () {
            syncTrigger(select, label);
            refreshSelectedStates(menu);
        });
    }

    function init() {
        document.querySelectorAll('select.tich-input, select.tich-select').forEach(enhanceSelect);
    }

    document.addEventListener('click', function (event) {
        if (!openDropdown) {
            return;
        }

        if (!openDropdown.contains(event.target)) {
            closeDropdown(openDropdown);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && openDropdown) {
            closeDropdown(openDropdown);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.TichSelect = {
        refresh: init,
    };
})();
