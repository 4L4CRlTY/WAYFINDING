(function () {
    'use strict';

    const SEARCH_PARAM = 'search';
    const PAGE_PARAM = 'page';

    function lockAdminPalette() {
        const html = document.documentElement;

        if (html.getAttribute('data-bs-theme') !== 'light') {
            html.setAttribute('data-bs-theme', 'light');
        }

        if (html.getAttribute('data-topbar-color') !== 'light') {
            html.setAttribute('data-topbar-color', 'light');
        }

        if (html.getAttribute('data-menu-color') !== 'brand') {
            html.setAttribute('data-menu-color', 'brand');
        }

        try {
            sessionStorage.removeItem('__CONFIG__');
        } catch (error) {
            // The palette still works when storage is unavailable.
        }
    }

    function pageUrlWithoutSearch() {
        const url = new URL(window.location.href);
        url.searchParams.delete(SEARCH_PARAM);
        url.searchParams.delete(PAGE_PARAM);

        return url.pathname + (url.searchParams.size ? `?${url.searchParams.toString()}` : '');
    }

    function addPreservedQueryInputs(form) {
        const query = new URLSearchParams(window.location.search);

        query.forEach(function (value, key) {
            if (key === SEARCH_PARAM || key === PAGE_PARAM) {
                return;
            }

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = key;
            hidden.value = value;
            form.appendChild(hidden);
        });
    }

    function createSearchToolbar(anchor, index) {
        const query = new URLSearchParams(window.location.search);
        const value = query.get(SEARCH_PARAM) || '';
        const toolbar = document.createElement('div');
        const form = document.createElement('form');
        const label = document.createElement('label');
        const inputShell = document.createElement('div');
        const icon = document.createElement('i');
        const input = document.createElement('input');
        const actions = document.createElement('div');
        const submit = document.createElement('button');

        toolbar.className = 'admin-table-toolbar';
        toolbar.dataset.adminTableToolbar = String(index);

        form.className = 'admin-table-search-form';
        form.method = 'get';
        form.action = window.location.pathname;
        form.setAttribute('role', 'search');

        label.className = 'admin-table-search-label';
        label.htmlFor = `admin-table-search-${index}`;
        label.innerHTML = '<span>Search records</span><small>Search the complete list, including other pages.</small>';

        inputShell.className = 'admin-table-search-input-shell';
        icon.className = 'ri-search-2-line';
        icon.setAttribute('aria-hidden', 'true');

        input.id = `admin-table-search-${index}`;
        input.className = 'admin-table-search-input';
        input.type = 'search';
        input.name = SEARCH_PARAM;
        input.value = value;
        input.placeholder = 'Type a name, code, type, or related record';
        input.autocomplete = 'off';

        actions.className = 'admin-table-search-actions';

        submit.className = 'admin-table-search-submit';
        submit.type = 'submit';
        submit.innerHTML = '<i class="ri-search-2-line" aria-hidden="true"></i><span>Search</span>';

        inputShell.append(icon, input);
        actions.appendChild(submit);

        if (value) {
            const clear = document.createElement('a');
            clear.className = 'admin-table-search-clear';
            clear.href = pageUrlWithoutSearch();
            clear.innerHTML = '<i class="ri-close-line" aria-hidden="true"></i><span>Clear</span>';
            actions.prepend(clear);

            const status = document.createElement('p');
            status.className = 'admin-table-search-status';
            status.setAttribute('role', 'status');
            status.textContent = `Showing matches for “${value}”.`;
            toolbar.appendChild(status);
        }

        addPreservedQueryInputs(form);
        form.append(label, inputShell, actions);
        toolbar.prepend(form);
        anchor.parentNode.insertBefore(toolbar, anchor);
    }

    function enhanceExistingSearch() {
        const existingInput = document.querySelector(
            '.content-page input[name="' + SEARCH_PARAM + '"]'
        );

        if (!existingInput) {
            return false;
        }

        const form = existingInput.closest('form');

        if (form) {
            form.setAttribute('role', 'search');
            form.classList.add('admin-existing-search-form');
        }

        existingInput.setAttribute('aria-label', 'Search all records');
        return true;
    }

    function installSearchTools() {
        if (enhanceExistingSearch()) {
            return;
        }

        const anchors = [];

        document.querySelectorAll('.content-page .table-responsive').forEach(function (element) {
            if (!anchors.includes(element)) {
                anchors.push(element);
            }
        });

        document.querySelectorAll('.content-page table').forEach(function (table) {
            const anchor = table.closest('.table-responsive, .dk-table-wrap') || table;

            if (!anchors.includes(anchor)) {
                anchors.push(anchor);
            }
        });

        const destinationEmptyState = document.querySelector('.content-page .dk-empty');

        if (destinationEmptyState && !anchors.includes(destinationEmptyState)) {
            anchors.push(destinationEmptyState);
        }

        const authorizedDirectory = document.querySelector('.authorized-directory-list');

        if (authorizedDirectory && !anchors.includes(authorizedDirectory)) {
            anchors.push(authorizedDirectory);
        }

        anchors.forEach(function (anchor, index) {
            const parent = anchor.parentElement;

            if (!parent || parent.querySelector(':scope > [data-admin-table-toolbar]')) {
                return;
            }

            createSearchToolbar(anchor, index + 1);
        });
    }

    function compactLegacyPagination() {
        document.querySelectorAll('.custom-pagination, .dk-custom-pagination').forEach(function (list) {
            list.setAttribute('aria-label', 'Table pages');

            const numericItems = Array.from(list.children).filter(function (item) {
                return /^\d+$/.test(item.textContent.trim());
            });

            if (numericItems.length <= 7) {
                return;
            }

            const activeIndex = numericItems.findIndex(function (item) {
                return item.classList.contains('active');
            });
            const visible = new Set([0, numericItems.length - 1]);

            for (let offset = -1; offset <= 1; offset += 1) {
                const target = activeIndex + offset;

                if (target >= 0 && target < numericItems.length) {
                    visible.add(target);
                }
            }

            numericItems.forEach(function (item, index) {
                item.classList.toggle('admin-pagination-hidden', !visible.has(index));
            });

            const shownItems = numericItems.filter(function (item) {
                return !item.classList.contains('admin-pagination-hidden');
            });

            shownItems.forEach(function (item, index) {
                if (index === 0) {
                    return;
                }

                const previousIndex = numericItems.indexOf(shownItems[index - 1]);
                const currentIndex = numericItems.indexOf(item);

                if (currentIndex - previousIndex <= 1) {
                    return;
                }

                const ellipsis = document.createElement('li');
                ellipsis.className = 'admin-pagination-ellipsis';
                ellipsis.innerHTML = '<span aria-hidden="true">…</span><span class="visually-hidden">Skipped pages</span>';
                list.insertBefore(ellipsis, item);
            });
        });
    }

    function initialize() {
        lockAdminPalette();
        installSearchTools();
        compactLegacyPagination();

        const observer = new MutationObserver(lockAdminPalette);
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-bs-theme', 'data-topbar-color', 'data-menu-color'],
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
