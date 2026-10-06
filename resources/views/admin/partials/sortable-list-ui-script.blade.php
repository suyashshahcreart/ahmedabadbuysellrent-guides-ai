<script>
window.initSortableListUi = function (options) {
    var tbody = document.getElementById(options.tbodyId);
    var searchInput = document.getElementById(options.searchId);
    var perPageSelect = document.getElementById(options.perPageId);
    var infoEl = document.getElementById(options.infoId);
    var paginationEl = document.getElementById(options.paginationId);
    var emptyRow = options.emptyRowId ? document.getElementById(options.emptyRowId) : null;
    var countBadge = options.countBadgeId ? document.getElementById(options.countBadgeId) : null;
    var itemPlural = options.itemPlural || 'items';
    var currentPage = 1;
    var debounceTimer;

    if (!tbody || !searchInput || !perPageSelect || !infoEl || !paginationEl) {
        return { refresh: function () {} };
    }

    function getDataRows() {
        return Array.from(tbody.querySelectorAll('tr[data-id]'));
    }

    function getSearchableText(row) {
        return (row.dataset.search || row.textContent || '').toLowerCase();
    }

    function getMatchedRows() {
        var q = (searchInput.value || '').trim().toLowerCase();
        return getDataRows().filter(function (row) {
            return !q || getSearchableText(row).indexOf(q) !== -1;
        });
    }

    function updateVisibleSrNumbers(visibleRows, startIndex) {
        visibleRows.forEach(function (row, idx) {
            var srCell = row.querySelector('.cat-sr-no');
            if (srCell) srCell.textContent = startIndex + idx + 1;
        });
    }

    function renderPagination(total, perPage) {
        var totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) currentPage = totalPages;

        paginationEl.innerHTML = '';
        if (totalPages <= 1) return;

        function addItem(label, page, disabled, active) {
            var li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            var link = document.createElement('a');
            link.className = 'page-link';
            link.href = '#';
            link.textContent = label;
            if (!disabled && !active) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    currentPage = page;
                    apply();
                });
            }
            li.appendChild(link);
            paginationEl.appendChild(li);
        }

        addItem('Previous', currentPage - 1, currentPage === 1, false);

        for (var p = 1; p <= totalPages; p++) {
            if (totalPages > 7 && Math.abs(p - currentPage) > 2 && p !== 1 && p !== totalPages) {
                if (p === 2 || p === totalPages - 1) {
                    var dots = document.createElement('li');
                    dots.className = 'page-item disabled';
                    dots.innerHTML = '<span class="page-link">…</span>';
                    paginationEl.appendChild(dots);
                }
                continue;
            }
            addItem(String(p), p, false, p === currentPage);
        }

        addItem('Next', currentPage + 1, currentPage === totalPages, false);
    }

    function apply() {
        var perPage = parseInt(perPageSelect.value, 10) || 10;
        var matched = getMatchedRows();
        var total = matched.length;
        var allRows = getDataRows();

        allRows.forEach(function (row) { row.style.display = 'none'; });

        if (total === 0) {
            if (emptyRow) {
                if (allRows.length > 0) {
                    emptyRow.classList.remove('d-none');
                } else {
                    emptyRow.classList.add('d-none');
                }
            }
            infoEl.textContent = allRows.length
                ? 'Showing 0 entries (filtered from ' + allRows.length + ' total)'
                : 'Showing 0 entries';
            if (countBadge) countBadge.textContent = '0 ' + itemPlural;
            paginationEl.innerHTML = '';
            return;
        }

        if (emptyRow) emptyRow.classList.add('d-none');

        var start = (currentPage - 1) * perPage;
        var visible = matched.slice(start, start + perPage);
        visible.forEach(function (row) { row.style.display = ''; });

        var from = start + 1;
        var to = start + visible.length;
        infoEl.textContent = 'Showing ' + from + ' to ' + to + ' of ' + total + ' entries'
            + (total !== allRows.length ? ' (filtered from ' + allRows.length + ' total)' : '');

        if (countBadge) countBadge.textContent = total + ' ' + itemPlural;

        updateVisibleSrNumbers(visible, start);
        renderPagination(total, perPage);
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            currentPage = 1;
            apply();
        }, 200);
    });

    perPageSelect.addEventListener('change', function () {
        currentPage = 1;
        apply();
    });

    apply();

    return {
        refresh: apply,
        resetPage: function () {
            currentPage = 1;
            apply();
        },
    };
};
</script>
