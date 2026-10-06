/**
 * Report table column visibility picker.
 *
 * bind(picker, dataTable, columns, storageKey, options?)
 * options: {
 *   initialVisibleKeys?: string[],
 *   onPersist?: (keys: string[]) => void,
 *   footerText?: string,
 *   useLocalStorage?: boolean (default true)
 * }
 */
(function (window) {
    'use strict';

    function loadVisibleKeys(storageKey, columns, initialVisibleKeys) {
        if (Array.isArray(initialVisibleKeys) && initialVisibleKeys.length) {
            const allowed = columns.map(function (c) { return c.key; });
            const filtered = initialVisibleKeys.filter(function (k) { return allowed.indexOf(k) !== -1; });
            if (filtered.length) {
                return filtered;
            }
        }

        try {
            const raw = localStorage.getItem(storageKey);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed) && parsed.length) {
                    const allowed = columns.map(function (c) { return c.key; });
                    const filtered = parsed.filter(function (k) { return allowed.indexOf(k) !== -1; });
                    if (filtered.length) {
                        return filtered;
                    }
                }
            }
        } catch (e) { /* ignore */ }

        return columns.filter(function (c) { return c.defaultVisible !== false; }).map(function (c) { return c.key; });
    }

    function saveVisibleKeys(storageKey, keys) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(keys));
        } catch (e) { /* ignore */ }
    }

    function renderHeader(tableSelector, columns) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        let thead = table.querySelector('thead');
        if (!thead) {
            thead = document.createElement('thead');
            table.appendChild(thead);
        }
        thead.innerHTML = '<tr>' + columns.map(function (c) {
            return '<th>' + c.title + '</th>';
        }).join('') + '</tr>';
    }

    function dataTableColumns(columns, storageKey, options) {
        options = options || {};
        const visible = loadVisibleKeys(storageKey, columns, options.initialVisibleKeys);
        return columns.map(function (c) {
            return {
                data: c.data,
                name: c.key,
                title: c.title,
                visible: visible.indexOf(c.key) !== -1,
                orderable: c.orderable !== false,
                searchable: c.searchable !== false,
                className: c.className || '',
                width: c.width || undefined,
                defaultContent: '—',
            };
        });
    }

    function bind(pickerSelector, dataTable, columns, storageKey, options) {
        const picker = document.querySelector(pickerSelector);
        if (!picker || !dataTable) return;

        options = options || {};
        const useLocalStorage = options.useLocalStorage !== false;
        const footerText = options.footerText || 'Choices are saved for this browser.';
        const visible = loadVisibleKeys(storageKey, columns, options.initialVisibleKeys);
        const visibleCount = visible.length;
        const totalCount = columns.length;

        // Clone node to drop previous listeners when rebinding after form filter change.
        const fresh = picker.cloneNode(false);
        picker.parentNode.replaceChild(fresh, picker);
        const pickerEl = fresh;

        pickerEl.innerHTML =
            '<div class="report-col-picker-header">' +
                '<div>' +
                    '<p class="title">Show columns</p>' +
                    '<p class="subtitle"><span id="reportColVisibleCount">' + visibleCount + '</span> of ' + totalCount + ' visible</p>' +
                '</div>' +
                '<div class="btn-group btn-group-sm report-col-picker-actions">' +
                    '<button type="button" class="btn btn-outline-primary" data-col-action="all">All</button>' +
                    '<button type="button" class="btn btn-outline-secondary" data-col-action="defaults">Default</button>' +
                '</div>' +
            '</div>' +
            '<div class="report-col-picker-body">' +
                '<div class="report-col-picker-grid">' +
                    columns.map(function (c, idx) {
                        const checked = visible.indexOf(c.key) !== -1 ? ' checked' : '';
                        const safeId = String(c.key).replace(/[^a-zA-Z0-9_-]/g, '_');
                        return '<label class="report-col-picker-item" for="rptCol_' + safeId + '">' +
                            '<input class="form-check-input report-col-toggle" type="checkbox" value="' + idx + '" data-key="' + c.key + '" id="rptCol_' + safeId + '"' + checked + '>' +
                            '<span class="form-check-label">' + c.title + '</span>' +
                            '</label>';
                    }).join('') +
                '</div>' +
            '</div>' +
            '<div class="report-col-picker-footer">' + footerText + '</div>';

        function updateCountLabel() {
            const countEl = pickerEl.querySelector('#reportColVisibleCount');
            if (countEl) {
                countEl.textContent = String(pickerEl.querySelectorAll('.report-col-toggle:checked').length);
            }
        }

        function persistFromUi() {
            const keys = Array.from(pickerEl.querySelectorAll('.report-col-toggle:checked'))
                .map(function (el) { return el.getAttribute('data-key'); });
            if (useLocalStorage) {
                saveVisibleKeys(storageKey, keys);
            }
            updateCountLabel();
            if (typeof options.onPersist === 'function') {
                options.onPersist(keys);
            }
        }

        function applyVisibility() {
            pickerEl.querySelectorAll('.report-col-toggle').forEach(function (el) {
                const idx = parseInt(el.value, 10);
                dataTable.column(idx).visible(el.checked, false);
            });
            dataTable.columns.adjust().draw(false);
            persistFromUi();
        }

        pickerEl.addEventListener('change', function (e) {
            const el = e.target;
            if (!el.classList.contains('report-col-toggle')) return;

            const checkedCount = pickerEl.querySelectorAll('.report-col-toggle:checked').length;
            if (!el.checked && checkedCount === 0) {
                el.checked = true;
                return;
            }

            applyVisibility();
        });

        pickerEl.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-col-action]');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            const action = btn.getAttribute('data-col-action');
            pickerEl.querySelectorAll('.report-col-toggle').forEach(function (el) {
                if (action === 'all') {
                    el.checked = true;
                } else if (action === 'defaults') {
                    const key = el.getAttribute('data-key');
                    const def = columns.find(function (c) { return c.key === key; });
                    el.checked = !def || def.defaultVisible !== false;
                }
            });
            applyVisibility();
        });
    }

    window.ReportColumnPicker = {
        renderHeader: renderHeader,
        dataTableColumns: dataTableColumns,
        bind: bind,
        loadVisibleKeys: loadVisibleKeys,
    };
})(window);
