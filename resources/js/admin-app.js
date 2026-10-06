import $ from 'jquery';
window.$ = window.jQuery = $;

import DataTable from 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import 'simplebar';
import 'iconify-icon';

import Swal from 'sweetalert2';
window.Swal = Swal;

class PanelCard {
    constructor() {
        this.cardSelector = '[data-panel-card]';
    }

    init() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-panel-action]');
            if (!button) return;
            const card = button.closest(this.cardSelector);
            if (!card) return;

            const action = button.dataset.panelAction;
            switch (action) {
                case 'refresh':
                    this.handleRefresh(card);
                    break;
                case 'collapse':
                    this.handleCollapse(card, button);
                    break;
                case 'fullscreen':
                    this.handleFullscreen(card, button);
                    break;
                case 'close':
                    this.handleClose(card);
                    break;
                default:
                    break;
            }
        });
    }

    handleRefresh(card) {
        if (card.querySelector('.panel-overlay')) return;
        const table = card.querySelector('table.js-datatable');
        if (table && DataTable.isDataTable(table)) {
            new DataTable.Api(table).draw(false);
        }
        const overlay = document.createElement('div');
        overlay.className = 'panel-overlay';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        card.appendChild(overlay);
        setTimeout(() => overlay.remove(), 600);
    }

    handleCollapse(card, button) {
        card.querySelectorAll('.card-body, .card-footer').forEach((section) => {
            section.classList.toggle('d-none');
        });
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('ri-arrow-up-s-line');
            icon.classList.toggle('ri-arrow-down-s-line');
        }
        const body = card.querySelector('.card-body');
        if (body && !body.classList.contains('d-none') && typeof window.scheduleRefreshFaqAnswerEditorsIn === 'function') {
            window.scheduleRefreshFaqAnswerEditorsIn(card, 120);
        }
    }

    handleFullscreen(card, button) {
        const entering = !card.classList.contains('card-fullscreen');
        if (entering) {
            document.querySelectorAll(`${this.cardSelector}.card-fullscreen`).forEach((other) => {
                if (other !== card) this.exitFullscreen(other);
            });
            card.querySelectorAll('.card-body, .card-footer').forEach((section) => {
                section.classList.remove('d-none');
            });
            card._fsParent = card.parentNode;
            card._fsNext = card.nextSibling;
            document.body.appendChild(card);
            document.body.classList.add('panel-fullscreen-active');
            card.classList.add('card-fullscreen');
        } else {
            this.exitFullscreen(card, button);
        }
        const icon = button?.querySelector('i');
        if (icon) {
            icon.classList.toggle('ri-fullscreen-line', !card.classList.contains('card-fullscreen'));
            icon.classList.toggle('ri-fullscreen-exit-line', card.classList.contains('card-fullscreen'));
        }
    }

    exitFullscreen(card, button) {
        if (!card.classList.contains('card-fullscreen')) return;
        card.classList.remove('card-fullscreen');
        if (card._fsParent) {
            card._fsParent.insertBefore(card, card._fsNext ?? null);
            delete card._fsParent;
            delete card._fsNext;
        }
        if (!document.querySelector(`${this.cardSelector}.card-fullscreen`)) {
            document.body.classList.remove('panel-fullscreen-active');
        }
        const toggleButton = button || card.querySelector('[data-panel-action="fullscreen"]');
        const icon = toggleButton?.querySelector('i');
        if (icon) {
            icon.classList.add('ri-fullscreen-line');
            icon.classList.remove('ri-fullscreen-exit-line');
        }
    }

    handleClose(card) {
        if (card.querySelector('form')) {
            window.history.length > 1 ? window.history.back() : window.location.href = '/administrator';
            return;
        }
        card.remove();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PanelCard().init();
    initAdminDataTables();
    initDeleteConfirmations();
});

function initDeleteConfirmations() {
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.btn-delete-master');
        if (!btn) return;
        const form = btn.closest('form');
        if (!form || form.dataset.deleteConfirmed === '1') return;
        event.preventDefault();
        const name = btn.getAttribute('data-item-name') || 'this item';
        const proceed = () => {
            form.dataset.deleteConfirmed = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(btn);
            } else {
                form.submit();
            }
        };
        if (typeof Swal === 'undefined') {
            if (window.confirm(`Delete "${name}"?`)) proceed();
            return;
        }
        Swal.fire({
            title: 'Delete permanently?',
            html: `Are you sure you want to delete <em>${name}</em>? This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
        }).then((result) => {
            if (result.isConfirmed) proceed();
        });
    });
}

function initAdminDataTables() {
    document.querySelectorAll('table.js-datatable').forEach((el) => {
        if (DataTable.isDataTable(el)) return;

        const noOrderTargets = [];
        el.querySelectorAll('thead th').forEach((th, i) => {
            if (th.classList.contains('no-sort') || th.classList.contains('text-end')) {
                noOrderTargets.push(i);
            }
        });

        new DataTable(el, {
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [],
            autoWidth: false,
            columnDefs: noOrderTargets.length ? [{ orderable: false, targets: noOrderTargets }] : [],
            language: {
                search: '_INPUT_',
                searchPlaceholder: el.getAttribute('data-search') || 'Search...',
                lengthMenu: '_MENU_ entries per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ results',
                infoEmpty: 'Showing 0 results',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching records found',
            },
        });
    });

    document.addEventListener('change', (event) => {
        const sel = event.target.closest('.js-dt-filter');
        if (!sel) return;
        const tableEl = document.querySelector(sel.getAttribute('data-table'));
        if (!tableEl || !DataTable.isDataTable(tableEl)) return;
        const col = parseInt(sel.getAttribute('data-column'), 10);
        new DataTable.Api(tableEl).column(col).search(sel.value || '', false, false).draw();
    });
}
