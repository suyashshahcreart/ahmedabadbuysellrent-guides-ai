/**
 * Shared FAQ inline panel (create forms) — sortable rows + small answer editor.
 *
 * initFaqInlinePanel({
 *   containerId, addBtnId, expandAllBtnId, collapseAllBtnId,
 *   questionPlaceholder, initialCount,
 *   rowClass, sortOrderClass, dragHandleClass, headerClass, toggleClass,
 *   rowLabelClass, summaryClass, chevronClass, bodyClass, questionInputClass,
 *   removeBtnClass, bodyIdPrefix, editorIdPrefix,
 * });
 */
window.initFaqInlinePanel = function (cfg) {
    var container = document.getElementById(cfg.containerId);
    var addBtn = document.getElementById(cfg.addBtnId);

    if (!container || !addBtn || container.dataset.faqsInit === '1') {
        return;
    }

    container.dataset.faqsInit = '1';

    var questionPlaceholder = cfg.questionPlaceholder || '';
    var faqUidSeq = cfg.initialCount || 0;

    function rowSelector() {
        return '.' + cfg.rowClass;
    }

    function ensureAnswerEditor(row) {
        window.ensureFaqAnswerEditorForRow(row, {
            onBlur: function () {
                var answerInput = row.querySelector('.faq-answer-input');
                if (answerInput && answerInput.id && typeof window.getFaqAnswerHtml === 'function') {
                    answerInput.value = window.getFaqAnswerHtml(answerInput.id);
                }
            },
        });
    }

    function destroyAnswerEditor(row) {
        window.destroyFaqAnswerEditorForRow(row);
    }

    function updateSummary(row) {
        var summary = row.querySelector('.' + cfg.summaryClass);
        var questionInput = row.querySelector('.' + cfg.questionInputClass);
        if (!summary || !questionInput) {
            return;
        }

        var text = questionInput.value.trim();
        summary.textContent = text !== '' ? text : 'New FAQ';
    }

    function reindexRows() {
        container.querySelectorAll(rowSelector()).forEach(function (row, index) {
            var label = row.querySelector('.' + cfg.rowLabelClass);
            if (label) {
                label.textContent = 'FAQ ' + (index + 1);
            }

            row.querySelectorAll('[name^="faqs["]').forEach(function (input) {
                var field = input.name.replace(/^faqs\[\d+\]/, '');
                input.name = 'faqs[' + index + ']' + field;
            });

            var body = row.querySelector('.' + cfg.bodyClass);
            var toggle = row.querySelector('.' + cfg.toggleClass);
            var bodyId = cfg.bodyIdPrefix + '-' + index;
            if (body) {
                body.id = bodyId;
            }
            if (toggle) {
                toggle.setAttribute('data-bs-target', '#' + bodyId);
                toggle.setAttribute('aria-controls', bodyId);
            }

            var activeInput = row.querySelector('[name$="[is_active]"]');
            if (activeInput) {
                activeInput.id = 'faq_active_' + index;
                var activeLabel = row.querySelector('label[for^="faq_active_"]');
                if (activeLabel) {
                    activeLabel.setAttribute('for', 'faq_active_' + index);
                }
            }

            var sortInput = row.querySelector('.' + cfg.sortOrderClass);
            if (sortInput) {
                sortInput.value = index;
            }

            updateSummary(row);

            var chevron = row.querySelector('.' + cfg.chevronClass);
            var faqBody = row.querySelector('.' + cfg.bodyClass);
            if (chevron && faqBody) {
                chevron.classList.toggle('rotated', faqBody.classList.contains('show'));
            }
        });

        var rows = container.querySelectorAll(rowSelector());
        rows.forEach(function (row) {
            var removeBtn = row.querySelector('.' + cfg.removeBtnClass);
            if (removeBtn) {
                removeBtn.disabled = rows.length <= 1;
            }
        });
    }

    function createFaqRow(expand) {
        var editorId = cfg.editorIdPrefix + '-u' + (++faqUidSeq);
        var row = document.createElement('div');
        row.className = cfg.rowClass + ' border rounded mb-3 bg-light-subtle overflow-hidden';
        row.innerHTML =
            '<input type="hidden" class="' + cfg.sortOrderClass + '" name="faqs[0][sort_order]" value="0">' +
            '<div class="' + cfg.headerClass + ' d-flex align-items-center gap-2 px-3 py-2 border-bottom bg-white">' +
                '<span class="' + cfg.dragHandleClass + ' text-muted" title="Drag to reorder" style="cursor:grab;">' +
                    '<i class="ri-drag-move-2-line fs-5"></i>' +
                '</span>' +
                '<button type="button" class="btn btn-sm btn-link text-decoration-none text-dark p-0 ' + cfg.toggleClass + ' flex-grow-1 text-start d-flex align-items-center gap-1" data-bs-toggle="collapse">' +
                    '<span class="badge bg-soft-primary text-primary ' + cfg.rowLabelClass + '">FAQ</span>' +
                    '<span class="' + cfg.summaryClass + ' text-truncate">New FAQ</span>' +
                    '<i class="ri-arrow-down-s-line ms-auto ' + cfg.chevronClass + '"></i>' +
                '</button>' +
                '<div class="form-check form-switch mb-0 flex-shrink-0">' +
                    '<input class="form-check-input" type="checkbox" value="1" checked>' +
                    '<label class="form-check-label small">Active</label>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger ' + cfg.removeBtnClass + ' flex-shrink-0" title="Remove FAQ">' +
                    '<i class="ri-delete-bin-line"></i>' +
                '</button>' +
            '</div>' +
            '<div class="collapse ' + cfg.bodyClass + ' px-3 py-3">' +
                '<div class="mb-3">' +
                    '<label class="form-label small fw-semibold">Question</label>' +
                    '<input type="text" class="form-control form-control-sm ' + cfg.questionInputClass + '" maxlength="1000">' +
                '</div>' +
                '<div class="mb-0">' +
                    '<label class="form-label small fw-semibold">Answer</label>' +
                    '<textarea id="' + editorId + '" rows="3" class="form-control form-control-sm faq-answer-input" placeholder="Provide a clear, helpful answer..."></textarea>' +
                '</div>' +
            '</div>';

        var questionInput = row.querySelector('.' + cfg.questionInputClass);
        var answerInput = row.querySelector('.faq-answer-input');
        var activeInput = row.querySelector('input[type="checkbox"]');

        questionInput.placeholder = questionPlaceholder;
        questionInput.name = 'faqs[0][question]';
        answerInput.name = 'faqs[0][answer]';
        activeInput.name = 'faqs[0][is_active]';
        activeInput.id = 'faq_active_new';
        var activeLabel = row.querySelector('label.form-check-label');
        if (activeLabel) {
            activeLabel.setAttribute('for', 'faq_active_new');
        }

        questionInput.addEventListener('input', function () {
            updateSummary(row);
        });

        if (expand) {
            var body = row.querySelector('.' + cfg.bodyClass);
            if (body) {
                body.classList.add('show');
            }
        }

        return row;
    }

    if (typeof Sortable !== 'undefined') {
        new Sortable(container, {
            animation: 150,
            handle: '.' + cfg.dragHandleClass,
            draggable: rowSelector(),
            onEnd: function () {
                reindexRows();
                if (typeof window.notifyPostEditorChanged === 'function') {
                    window.notifyPostEditorChanged();
                }
            },
        });
    }

    function scheduleEditorRefresh(delayMs) {
        var panel = container.closest('[data-panel-card]') || container;
        if (typeof window.scheduleRefreshFaqAnswerEditorsIn === 'function') {
            window.scheduleRefreshFaqAnswerEditorsIn(panel, delayMs);
            return;
        }

        window.refreshFaqAnswerEditorsIn(panel);
    }

    function notifyEditorChanged() {
        if (typeof window.notifyPostEditorChanged === 'function') {
            window.notifyPostEditorChanged();
        }
    }

    addBtn.addEventListener('click', function () {
        var row = createFaqRow(true);
        container.appendChild(row);
        reindexRows();
        scheduleEditorRefresh(80);
        notifyEditorChanged();
    });

    container.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('.' + cfg.removeBtnClass);
        if (!removeBtn || removeBtn.disabled) {
            return;
        }

        var row = removeBtn.closest(rowSelector());
        if (!row || container.querySelectorAll(rowSelector()).length <= 1) {
            return;
        }

        destroyAnswerEditor(row);
        row.remove();
        reindexRows();
        notifyEditorChanged();
    });

    container.querySelectorAll('.' + cfg.questionInputClass).forEach(function (input) {
        input.addEventListener('input', function () {
            updateSummary(input.closest(rowSelector()));
        });
    });

    if (cfg.expandAllBtnId) {
        document.getElementById(cfg.expandAllBtnId)?.addEventListener('click', function () {
            container.querySelectorAll('.' + cfg.bodyClass).forEach(function (body) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    bootstrap.Collapse.getOrCreateInstance(body, { toggle: false }).show();
                } else {
                    body.classList.add('show');
                }
            });
            container.querySelectorAll(rowSelector()).forEach(function (row) {
                ensureAnswerEditor(row);
            });
            scheduleEditorRefresh(350);
        });
    }

    if (cfg.collapseAllBtnId) {
        document.getElementById(cfg.collapseAllBtnId)?.addEventListener('click', function () {
            container.querySelectorAll('.' + cfg.bodyClass).forEach(function (body) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    bootstrap.Collapse.getOrCreateInstance(body, { toggle: false }).hide();
                } else {
                    body.classList.remove('show');
                }
            });
        });
    }

    container.addEventListener('show.bs.collapse', function (e) {
        var row = e.target.closest(rowSelector());
        if (row) {
            var chevron = row.querySelector('.' + cfg.chevronClass);
            if (chevron) {
                chevron.classList.add('rotated');
            }
        }
    });

    container.addEventListener('shown.bs.collapse', function (e) {
        var row = e.target.closest(rowSelector());
        if (row) {
            ensureAnswerEditor(row);
        }
    });

    container.addEventListener('hide.bs.collapse', function (e) {
        var row = e.target.closest(rowSelector());
        if (row) {
            var chevron = row.querySelector('.' + cfg.chevronClass);
            if (chevron) {
                chevron.classList.remove('rotated');
            }
        }
    });

    reindexRows();

    container.querySelectorAll(rowSelector()).forEach(function (row) {
        if (row.querySelector('.' + cfg.bodyClass + '.show')) {
            ensureAnswerEditor(row);
        }
    });

    scheduleEditorRefresh(120);

    var form = container.closest('form');
    if (form && !form.dataset.faqEditorSync) {
        form.dataset.faqEditorSync = '1';
        form.addEventListener('submit', function () {
            if (typeof syncAllFaqAnswerEditors === 'function') {
                syncAllFaqAnswerEditors(container);
            }
        });
    }
};
