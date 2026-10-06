/**
 * Bulk select + archive/delete for blog/case study post DataTables.
 * Selection is kept across page changes and table redraws.
 */
window.initPostBulkActions = function (tableSelector, options) {
    if (!window.jQuery) {
        return;
    }

    options = options || {};
    var $ = window.jQuery;
    var table = $(tableSelector);
    if (!table.length) {
        return;
    }

    var bar = document.querySelector(options.barSelector || '');
    var countEl = bar ? bar.querySelector('.post-bulk-count') : null;
    var archiveBtn = bar ? bar.querySelector('.btn-post-bulk-archive') : null;
    var recoverBtn = bar ? bar.querySelector('.btn-post-bulk-recover') : null;
    var deleteBtn = bar ? bar.querySelector('.btn-post-bulk-delete') : null;
    var bulkActionSelects = options.bulkActionSelectSelector
        ? document.querySelectorAll(options.bulkActionSelectSelector)
        : [];
    var bulkActionApplyButtons = options.bulkActionApplySelector
        ? document.querySelectorAll(options.bulkActionApplySelector)
        : [];
    var selectAll = document.querySelector(options.selectAllSelector || '');
    var selectedIds = new Set();

    function getDataTable() {
        if (!$.fn.DataTable || !$.fn.DataTable.isDataTable(table)) {
            return null;
        }
        return table.DataTable();
    }

    function pageCheckboxes() {
        return table.find('.post-bulk-checkbox');
    }

    function selectedCheckboxes() {
        return pageCheckboxes().filter(':checked');
    }

    function selectedIdsArray() {
        return Array.from(selectedIds);
    }

    function syncCheckboxToSet(checkbox, checked) {
        var id = parseInt(checkbox.value, 10);
        if (Number.isNaN(id)) {
            return;
        }
        if (checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
    }

    function updateBar() {
        var totalSelected = selectedIds.size;
        var pageSelected = selectedCheckboxes().length;
        var pageTotal = pageCheckboxes().length;

        if (countEl) {
            countEl.textContent = String(totalSelected);
        }
        if (bar) {
            bar.classList.toggle('d-none', totalSelected === 0);
            bar.classList.toggle('d-flex', totalSelected > 0);
        }
        var selectAllEl = options.selectAllSelector
            ? table.find(options.selectAllSelector)[0]
            : selectAll;
        if (selectAllEl) {
            selectAllEl.checked = pageTotal > 0 && pageSelected === pageTotal;
            selectAllEl.indeterminate = pageSelected > 0 && pageSelected < pageTotal;
        }
    }

    function restoreSelection() {
        pageCheckboxes().each(function () {
            var id = parseInt(this.value, 10);
            this.checked = !Number.isNaN(id) && selectedIds.has(id);
        });
        updateBar();
    }

    function clearSelection() {
        selectedIds.clear();
        pageCheckboxes().prop('checked', false);
        var selectAllEl = options.selectAllSelector
            ? table.find(options.selectAllSelector)[0]
            : selectAll;
        if (selectAllEl) {
            selectAllEl.checked = false;
            selectAllEl.indeterminate = false;
        }
        updateBar();
    }

    function confirmBulkAction(config) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(window.confirm(config.text || 'Are you sure?'));
        }

        return Swal.fire({
            title: config.title || 'Confirm',
            text: config.text || '',
            icon: config.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: config.confirmText || 'Confirm',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: (config.confirmClass || 'btn btn-primary') + ' me-2',
                cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
        }).then(function (result) {
            return !!result.isConfirmed;
        });
    }

    function runBulkRequest(url, method, ids) {
        return fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': options.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ ids: ids }),
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json().then(function (payload) {
                return { ok: response.ok, payload: payload };
            });
        });
    }

    function reloadTable() {
        var dt = getDataTable();
        if (dt) {
            dt.ajax.reload(null, false);
        }
        clearSelection();
    }

    table.on('change', '.post-bulk-checkbox', function () {
        syncCheckboxToSet(this, this.checked);
        updateBar();
    });

    table.on('draw.dt', restoreSelection);

    if (options.selectAllSelector) {
        table.on('change', options.selectAllSelector, function () {
            var selectAll = this;
            pageCheckboxes().each(function () {
                this.checked = selectAll.checked;
                syncCheckboxToSet(this, selectAll.checked);
            });
            updateBar();
        });
    }

    if (archiveBtn && options.bulkArchiveUrl) {
        archiveBtn.addEventListener('click', function () {
            var ids = selectedIdsArray();
            if (!ids.length) {
                return;
            }

            confirmBulkAction({
                title: 'Archive selected posts?',
                text: 'Move ' + ids.length + ' selected post(s) to Archived Posts. You can recover them later.',
                confirmText: 'Archive',
                confirmClass: 'btn btn-warning',
                icon: 'question',
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                runBulkRequest(options.bulkArchiveUrl, 'PATCH', ids).then(function (result) {
                    if (!result.ok) {
                        var message = (result.payload && result.payload.message) || 'Bulk archive failed.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Archive failed', text: message });
                        } else {
                            window.alert(message);
                        }
                        return;
                    }

                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast(result.payload.message);
                    }
                    reloadTable();
                }).catch(function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Archive failed', text: 'Request failed. Please try again.' });
                    }
                });
            });
        });
    }

    if (recoverBtn && options.bulkRecoverUrl) {
        recoverBtn.addEventListener('click', function () {
            var ids = selectedIdsArray();
            if (!ids.length) {
                return;
            }

            confirmBulkAction({
                title: 'Recover selected posts?',
                text: 'Recover ' + ids.length + ' selected post(s) as drafts?',
                confirmText: 'Recover',
                confirmClass: 'btn btn-success',
                icon: 'question',
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                runBulkRequest(options.bulkRecoverUrl, 'PATCH', ids).then(function (result) {
                    if (!result.ok) {
                        var message = (result.payload && result.payload.message) || 'Bulk recover failed.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Recover failed', text: message });
                        } else {
                            window.alert(message);
                        }
                        return;
                    }

                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast(result.payload.message);
                    }
                    reloadTable();
                }).catch(function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Recover failed', text: 'Request failed. Please try again.' });
                    }
                });
            });
        });
    }

    if (deleteBtn && options.bulkDeleteUrl) {
        deleteBtn.addEventListener('click', function () {
            var ids = selectedIdsArray();
            if (!ids.length) {
                return;
            }

            confirmBulkAction({
                title: 'Delete selected posts permanently?',
                text: 'Permanently delete ' + ids.length + ' selected post(s)? This cannot be undone.',
                confirmText: 'Delete',
                confirmClass: 'btn btn-danger',
                icon: 'warning',
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                runBulkRequest(options.bulkDeleteUrl, 'DELETE', ids).then(function (result) {
                    if (!result.ok) {
                        var message = (result.payload && result.payload.message) || 'Bulk delete failed.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Delete failed', text: message });
                        } else {
                            window.alert(message);
                        }
                        return;
                    }

                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast(result.payload.message);
                    }
                    reloadTable();
                }).catch(function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Delete failed', text: 'Request failed. Please try again.' });
                    }
                });
            });
        });
    }

    function runConfiguredBulkArchive() {
        if (!options.bulkArchiveUrl) {
            return;
        }

        var ids = selectedIdsArray();
        if (!ids.length) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'No posts selected',
                    text: 'Select one or more posts using the checkboxes, then choose a bulk action.',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false,
                });
            } else {
                window.alert('Select one or more posts first.');
            }
            return;
        }

        confirmBulkAction({
            title: 'Move selected posts to Trash?',
            text: 'Move ' + ids.length + ' selected post(s) to Trash. You can recover them later.',
            confirmText: 'Move to Trash',
            confirmClass: 'btn btn-warning',
            icon: 'question',
        }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            runBulkRequest(options.bulkArchiveUrl, 'PATCH', ids).then(function (result) {
                if (!result.ok) {
                    var message = (result.payload && result.payload.message) || 'Bulk archive failed.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Trash failed', text: message });
                    } else {
                        window.alert(message);
                    }
                    return;
                }

                if (typeof window.showSuccessToast === 'function') {
                    window.showSuccessToast(result.payload.message);
                }
                reloadTable();
            }).catch(function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Trash failed', text: 'Request failed. Please try again.' });
                }
            });
        });
    }

    if (bulkActionApplyButtons.length && options.bulkArchiveUrl) {
        bulkActionApplyButtons.forEach(function (applyBtn) {
            applyBtn.addEventListener('click', function () {
                var actionValue = options.bulkActionValue || 'archive';
                var selectedAction = actionValue;

                bulkActionSelects.forEach(function (selectEl) {
                    if (selectEl && selectEl.value) {
                        selectedAction = selectEl.value;
                    }
                });

                if (!selectedAction) {
                    return;
                }

                if (selectedAction === 'archive') {
                    runConfiguredBulkArchive();
                }
            });
        });
    }
};
