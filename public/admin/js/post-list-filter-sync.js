/**
 * List-view tab links + status filter sync for WordPress-style post lists.
 */
(function (window) {
    'use strict';

    const LIST_VIEW_TO_STATUS = {
        published: 'published',
        draft: 'draft',
        featured: 'scheduled',
    };

    const STATUS_TO_LIST_VIEW = {
        published: 'published',
        draft: 'draft',
        scheduled: 'featured',
    };

    function listViewToStatus(listView) {
        return LIST_VIEW_TO_STATUS[listView] || '';
    }

    function statusToListView(status) {
        if (!status) {
            return 'all';
        }

        return STATUS_TO_LIST_VIEW[status] || 'all';
    }

    function statusLabelFor($statusFilter, status) {
        if (!$statusFilter || !$statusFilter.length || !status) {
            return '';
        }

        const $option = $statusFilter.find('option[value="' + status + '"]');

        if ($option.length) {
            return $option.text();
        }

        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function setActiveSubnavTab(subnavSelector, listView) {
        if (!window.jQuery) {
            return;
        }

        window.jQuery(subnavSelector).find('a[data-list-view]').each(function () {
            window.jQuery(this).toggleClass('is-active', String(window.jQuery(this).data('list-view')) === listView);
        });
    }

    function updateStatusSummaryText(options) {
        if (!window.jQuery) {
            return;
        }

        options = options || {};
        const $ = window.jQuery;
        const $statusInfo = $('#statusInfo');

        if (!$statusInfo.length) {
            return;
        }

        const activeListView = options.activeListView || 'all';
        const $statusFilter = options.$statusFilter;

        if (activeListView === 'trash') {
            $statusInfo.addClass('d-none');

            return;
        }

        $statusInfo.removeClass('d-none');

        const statusVal = $statusFilter && $statusFilter.length ? ($statusFilter.val() || '') : '';

        $statusInfo.text(
            statusVal
                ? ' | Status: ' + $statusFilter.find('option:selected').text()
                : ' | Status: All Status'
        );
    }

    /**
     * Sync list-view tab when the status dropdown changes.
     */
    function handleStatusFilterChange(ctx) {
        const statusVal = ctx.statusVal || '';
        const targetView = statusToListView(statusVal);
        const activeListView = ctx.activeListView;

        if (!statusVal && activeListView !== 'all') {
            ctx.applyListViewState('all', { clearStatus: true });

            return true;
        }

        if (statusVal && targetView !== 'all' && targetView !== activeListView) {
            ctx.applyListViewState(targetView);

            return true;
        }

        if (statusVal && targetView === 'all' && activeListView !== 'all') {
            ctx.applyListViewState('all', { skipStatusSet: true, setStatus: statusVal });

            return true;
        }

        return false;
    }

    window.PostListViewStatus = {
        listViewToStatus: listViewToStatus,
        statusToListView: statusToListView,
        statusLabelFor: statusLabelFor,
        setActiveSubnavTab: setActiveSubnavTab,
        updateStatusSummaryText: updateStatusSummaryText,
        handleStatusFilterChange: handleStatusFilterChange,
    };

    window.updatePostListSubnavLinks = function (options) {
        if (!window.jQuery) {
            return;
        }

        const $ = window.jQuery;
        options = options || {};

        const $subnav = $(options.subnavSelector || '.post-wp-subnav');
        if (!$subnav.length) {
            return;
        }

        const indexUrl = options.indexUrl || $subnav.data('index-url') || window.location.pathname;
        const trashUrl = options.trashUrl || $subnav.data('trash-url') || null;

        function appendFilterParams(params, includeStatus) {
            if (options.category) {
                params.set('category', options.category);
            }
            if (options.tag) {
                params.set('tag', options.tag);
            }
            if (options.author) {
                params.set('author', options.author);
            }
            if (includeStatus && options.status) {
                params.set('status', options.status);
            }
            if (options.startDate && options.endDate) {
                params.set('start_date', options.startDate);
                params.set('end_date', options.endDate);
            }
            if (Array.isArray(options.taxonomy)) {
                options.taxonomy.forEach(function (termId) {
                    if (termId) {
                        params.append('taxonomy[]', termId);
                    }
                });
            }
        }

        $subnav.find('a[data-list-view]').each(function () {
            const view = String($(this).data('list-view') || 'all');
            const useTrashUrl = $(this).data('use-trash-url') === 1 || $(this).data('use-trash-url') === '1';
            const params = new URLSearchParams();

            if (useTrashUrl && trashUrl) {
                appendFilterParams(params, false);
                const qs = params.toString();
                this.href = trashUrl + (qs ? '?' + qs : '');

                return;
            }

            params.set('list_view', view);
            appendFilterParams(params, view === 'all');

            this.href = indexUrl + '?' + params.toString();
        });
    };
})(window);
