(function () {
    function bindWpListSearch(inputId, tableId, countId) {
        var input = document.getElementById(inputId);
        var table = document.getElementById(tableId);
        var count = document.getElementById(countId);
        if (!input || !table) return;
        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-wp-row]'));
        function apply() {
            var q = (input.value || '').toLowerCase().trim();
            var shown = 0;
            rows.forEach(function (row) {
                var text = (row.getAttribute('data-search') || row.textContent || '').toLowerCase();
                var match = !q || text.indexOf(q) !== -1;
                row.classList.toggle('d-none', !match);
                if (match) shown++;
            });
            if (count) count.textContent = shown + ' item' + (shown === 1 ? '' : 's');
        }
        input.addEventListener('input', apply);
        apply();
    }
    window.bindWpListSearch = bindWpListSearch;

    document.addEventListener('change', function (e) {
        var form = e.target.closest('form.js-wp-auto-filter');
        if (form) form.submit();
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-wp-quick-edit]');
        if (!btn) return;
        e.preventDefault();
        var id = btn.getAttribute('data-wp-quick-edit');
        var row = document.getElementById('wp-quick-edit-' + id);
        if (!row) return;
        document.querySelectorAll('.wp-quick-edit-row').forEach(function (el) {
            if (el !== row) el.classList.add('d-none');
        });
        row.classList.toggle('d-none');
    });
})();
