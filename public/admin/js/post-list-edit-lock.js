/**
 * WordPress-style edit lock warnings on blog/case study post list pages.
 * Polls lock status for visible rows only — no full DataTable reload.
 */
window.initPostListEditLock = function (tableSelector, options) {
    if (!window.jQuery) {
        return;
    }

    options = options || {};
    var $ = window.jQuery;
    var table = $(tableSelector);

    if (!table.length) {
        return;
    }

    table.on('click', '.btn-post-edit-locked', function (event) {
        event.preventDefault();

        var button = $(this);
        var editUrl = button.data('edit-url') || button.attr('href');
        var lockUser = button.data('lock-user') || 'Another user';

        if (typeof Swal === 'undefined') {
            if (window.confirm(lockUser + ' is currently editing this post. Take over?')) {
                window.location.href = editUrl + (editUrl.indexOf('?') >= 0 ? '&' : '?') + 'takeover=1';
            }
            return;
        }

        Swal.fire({
            title: 'Post locked',
            html: '<p class="mb-2"><i class="ri-lock-line me-1"></i><strong>' + escapeHtml(lockUser) + '</strong> is currently editing this post.</p><p class="mb-0 text-muted small">You can take over editing or stay on this list. Taking over may discard their unsaved changes.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Take over',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-warning me-2',
                cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
        }).then(function (result) {
            if (result.isConfirmed && editUrl) {
                window.location.href = editUrl + (editUrl.indexOf('?') >= 0 ? '&' : '?') + 'takeover=1';
            }
        });
    });

    function getEditUrl(postId) {
        return String(options.editUrlTemplate || '').replace('__ID__', String(postId));
    }

    function buildLockBadge(lockInfo) {
        if (!lockInfo || !lockInfo.lock) {
            return '';
        }

        var lockName = escapeHtml(lockInfo.lock.user_name || 'Another user');

        if (lockInfo.is_locked) {
            return '<span class="badge bg-soft-warning text-warning ms-1 post-list-lock-badge" title="' + lockName + ' is editing">'
                + '<i class="ri-lock-line me-1"></i>' + lockName + '</span>';
        }

        if (lockInfo.is_mine) {
            return '<span class="badge bg-soft-success text-success ms-1 post-list-lock-badge" title="You are editing">'
                + '<i class="ri-edit-line me-1"></i>Editing</span>';
        }

        return '';
    }

    function updateRowLockUI(rowNode, postId, lockInfo) {
        var $row = $(rowNode);
        var $titleCell = $row.find('td.post-list-title-cell');

        if ($titleCell.length) {
            $titleCell.find('.post-list-lock-badge').remove();
            var badge = buildLockBadge(lockInfo);
            var isWordPressLayout = options.layout === 'wordpress' || $titleCell.find('.post-wp-title-cell').length > 0;

            if (badge) {
                if (isWordPressLayout) {
                    var $titleMain = $titleCell.find('.post-wp-title-main').first();
                    if ($titleMain.length) {
                        $titleMain.append(' ' + badge);
                    }
                } else {
                    var $titleStrong = $titleCell.find('.post-list-title-main strong').first();
                    if (!$titleStrong.length) {
                        $titleStrong = $titleCell.find('strong').first();
                    }
                    if ($titleStrong.length) {
                        $titleStrong.after(' ' + badge);
                    }
                }
            }

            if (isWordPressLayout) {
                var editUrl = getEditUrl(postId);
                var $editSlot = $titleCell.find('.post-list-edit-slot');
                if ($editSlot.length) {
                    if (lockInfo && lockInfo.is_locked) {
                        var lockUser = escapeHtml(lockInfo.lock.user_name || 'Another user');
                        $editSlot.html(
                            '<a href="' + escapeHtml(editUrl) + '" class="post-wp-action-link btn-post-edit-locked"'
                            + ' data-edit-url="' + escapeHtml(editUrl) + '"'
                            + ' data-lock-user="' + lockUser + '">Edit</a>'
                        );
                    } else {
                        $editSlot.html('<a href="' + escapeHtml(editUrl) + '" class="post-wp-action-link">Edit</a>');
                    }
                }

                var $titleLink = $titleCell.find('.post-wp-edit-link').first();
                if ($titleLink.length) {
                    if (lockInfo && lockInfo.is_locked) {
                        var lockedUser = escapeHtml(lockInfo.lock.user_name || 'Another user');
                        $titleLink
                            .addClass('post-wp-edit-locked btn-post-edit-locked')
                            .attr('data-edit-url', editUrl)
                            .attr('data-lock-user', lockedUser);
                    } else {
                        $titleLink
                            .removeClass('post-wp-edit-locked btn-post-edit-locked')
                            .removeAttr('data-edit-url')
                            .removeAttr('data-lock-user');
                    }
                }

                return;
            }
        }

        var $editSlot = $row.find('td.post-list-actions-cell .post-list-edit-slot');
        if (!$editSlot.length) {
            return;
        }

        var editUrl = getEditUrl(postId);
        if (lockInfo && lockInfo.is_locked) {
            var lockUser = escapeHtml(lockInfo.lock.user_name || 'Another user');
            $editSlot.html(
                '<a href="' + escapeHtml(editUrl) + '" class="' + (options.lockedEditBtnClass || '') + ' btn-post-edit-locked"'
                + ' title="' + lockUser + ' is editing"'
                + ' data-edit-url="' + escapeHtml(editUrl) + '"'
                + ' data-lock-user="' + lockUser + '">'
                + '<i class="ri-lock-line"></i></a>'
            );
            return;
        }

        $editSlot.html(
            '<a href="' + escapeHtml(editUrl) + '" class="' + (options.editBtnClass || '') + '" title="Edit">'
            + '<i class="ri-edit-line"></i></a>'
        );
    }

    function refreshVisibleRowLocks() {
        if (!options.lockStatusUrl || !$.fn.DataTable || !$.fn.DataTable.isDataTable(table)) {
            return;
        }

        var dt = table.DataTable();
        var ids = [];
        var rows = [];

        dt.rows({ page: 'current' }).every(function () {
            var data = this.data();
            if (data && data.id) {
                ids.push(data.id);
                rows.push({ id: data.id, node: this.node() });
            }
        });

        if (!ids.length) {
            return;
        }

        fetch(options.lockStatusUrl, {
            method: 'POST',
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
        }).then(function (result) {
            if (!result.ok || !result.payload || !result.payload.locks) {
                return;
            }

            rows.forEach(function (item) {
                var lockInfo = result.payload.locks[String(item.id)] || null;
                updateRowLockUI(item.node, item.id, lockInfo);
            });
        }).catch(function () {
            // Background poll — fail silently.
        });
    }

    if (options.autoRefreshMs && options.autoRefreshMs > 0) {
        window.setInterval(refreshVisibleRowLocks, options.autoRefreshMs);
    }

    table.on('draw.dt', function () {
        if (options.autoRefreshMs && options.autoRefreshMs > 0) {
            window.setTimeout(refreshVisibleRowLocks, 0);
        }
    });
};

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
