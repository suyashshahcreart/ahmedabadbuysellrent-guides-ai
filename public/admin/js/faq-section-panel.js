/**
 * AJAX FAQ section for entity edit pages (faq-section.blade.php).
 */
window.initFaqSectionPanel = function (cfg) {
    var sectionId = cfg.sectionId;
    var card = document.getElementById(sectionId + '-card');
    if (!card || card.dataset.faqsInit === '1') {
        return;
    }

    card.dataset.faqsInit = '1';

    var rowsEl = document.getElementById(sectionId + '-rows');
    var loadingEl = document.getElementById(sectionId + '-loading');
    var uidSeq = 0;
    var sortableInst = null;
    var saveTimers = {};

    function esc(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showLoading(on) {
        loadingEl.classList.toggle('d-none', !on);
    }

    function allRows() {
        return Array.from(rowsEl.querySelectorAll('.faq-row'));
    }

    function rowIndex(row) {
        return allRows().indexOf(row);
    }

    function reindexRows() {
        allRows().forEach(function (row, i) {
            var badge = row.querySelector('.faq-badge');
            if (badge) {
                badge.textContent = 'FAQ ' + (i + 1);
            }

            var removeBtn = row.querySelector('.btn-faq-remove');
            if (removeBtn) {
                removeBtn.disabled = allRows().length <= 1;
            }
        });
    }

    function updateSummary(row) {
        var qInput = row.querySelector('.faq-question-input');
        var summary = row.querySelector('.faq-row-summary');
        if (!qInput || !summary) {
            return;
        }

        summary.textContent = qInput.value.trim() || 'New FAQ';
    }

    function request(method, url, data, options) {
        options = options || {};
        var isGet = (method === 'GET');
        var payload = isGet ? null : Object.assign({}, data || {});
        if (payload && method !== 'POST') {
            payload._method = method;
        }

        return fetch(url, {
            method: isGet ? 'GET' : 'POST',
            keepalive: !!options.keepalive,
            headers: isGet
                ? { 'X-Requested-With': 'XMLHttpRequest' }
                : {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': cfg.csrf,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            body: payload ? JSON.stringify(payload) : undefined,
        }).then(function (r) {
            return r.json();
        });
    }

    function bodyId(key) {
        return sectionId + '-body-' + key;
    }

    function answerEditorId(key) {
        return sectionId + '-answer-' + key;
    }

    function destroyAllAnswerEditors() {
        allRows().forEach(function (row) {
            window.destroyFaqAnswerEditorForRow(row);
        });
    }

    function ensureAnswerEditor(row) {
        window.ensureFaqAnswerEditorForRow(row, {
            onChange: function () {
                schedulePersist(row);
            },
            onBlur: function () {
                flushPersistRow(row);
            },
        });
    }

    function getRowAnswerHtml(row) {
        var aInput = row.querySelector('.faq-answer-input');
        if (!aInput) {
            return '';
        }

        if (aInput.id && typeof window.getFaqAnswerHtml === 'function') {
            return window.getFaqAnswerHtml(aInput.id).trim();
        }

        return aInput.value.trim();
    }

    function buildRow(item, index, expanded) {
        var isSaved = !!(item && item.id);
        var key = isSaved ? String(item.id) : 'u' + (++uidSeq);
        var bid = bodyId(key);
        var aid = answerEditorId(key);
        var el = document.createElement('div');

        el.className = 'faq-row border rounded mb-3 bg-light-subtle overflow-hidden';
        if (isSaved) {
            el.dataset.id = item.id;
            el.dataset.origQ = item.question || '';
            el.dataset.origA = item.answer || '';
        } else {
            el.dataset.unsaved = '1';
            el.dataset.rowKey = key;
        }

        el.innerHTML =
            '<div class="faq-row-header d-flex align-items-center gap-2 px-3 py-2 border-bottom bg-white">' +
                '<span class="faq-row-handle text-muted" title="Drag to reorder" style="cursor:grab;">' +
                    '<i class="ri-drag-move-2-line fs-5"></i>' +
                '</span>' +
                '<button type="button"' +
                        ' class="btn btn-sm btn-link text-decoration-none text-dark p-0 faq-row-toggle flex-grow-1 text-start d-flex align-items-center gap-1"' +
                        ' data-bs-toggle="collapse"' +
                        ' data-bs-target="#' + bid + '"' +
                        ' aria-expanded="' + (expanded ? 'true' : 'false') + '"' +
                        ' aria-controls="' + bid + '">' +
                    '<span class="badge bg-soft-primary text-primary faq-badge">FAQ ' + (index + 1) + '</span>' +
                    '<span class="faq-row-summary text-truncate">' + esc((item && item.question) || 'New FAQ') + '</span>' +
                    '<i class="ri-arrow-down-s-line ms-auto faq-row-chevron' + (expanded ? ' rotated' : '') + '"></i>' +
                '</button>' +
                '<div class="form-check form-switch mb-0 flex-shrink-0">' +
                    '<input class="form-check-input faq-active-toggle" type="checkbox"' +
                           ' id="' + sectionId + '-act-' + key + '"' +
                           ' value="1"' + ((item && item.is_active !== false) ? ' checked' : '') + '>' +
                    '<label class="form-check-label small" for="' + sectionId + '-act-' + key + '">Active</label>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-faq-remove flex-shrink-0" title="Remove FAQ"' +
                        (isSaved ? ' data-id="' + item.id + '"' : '') + '>' +
                    '<i class="ri-delete-bin-line"></i>' +
                '</button>' +
            '</div>' +
            '<div id="' + bid + '" class="collapse faq-row-body px-3 py-3' + (expanded ? ' show' : '') + '">' +
                '<div class="mb-3">' +
                    '<label class="form-label small fw-semibold">Question</label>' +
                    '<input type="text" class="form-control form-control-sm faq-question-input"' +
                           ' value="' + esc((item && item.question) || '') + '"' +
                           ' maxlength="1000"' +
                           ' placeholder="' + esc(cfg.questionPlaceholder) + '">' +
                '</div>' +
                '<div class="mb-0">' +
                    '<label class="form-label small fw-semibold">Answer</label>' +
                    '<textarea id="' + aid + '" class="form-control form-control-sm faq-answer-input"' +
                              ' rows="3"' +
                              ' placeholder="Provide a clear, helpful answer..."></textarea>' +
                '</div>' +
            '</div>';

        var aInput = el.querySelector('.faq-answer-input');
        if (aInput && item && item.answer) {
            aInput.value = item.answer;
        }

        bindRowEvents(el);

        return el;
    }

    function appendDefaultRow(expanded) {
        var row = buildRow({ question: '', answer: '', is_active: true }, allRows().length, expanded !== false);
        rowsEl.appendChild(row);
        reindexRows();
        initSortable();
        return row;
    }

    function ensureAtLeastOneRow() {
        if (!allRows().length) {
            appendDefaultRow(true);
        }
    }

    function rowPayload(row) {
        return {
            question: row.querySelector('.faq-question-input').value.trim(),
            answer: getRowAnswerHtml(row),
            is_active: row.querySelector('.faq-active-toggle').checked ? 1 : 0,
            sort_order: rowIndex(row),
        };
    }

    function schedulePersist(row) {
        var key = row.dataset.id || row.dataset.rowKey;
        if (saveTimers[key]) {
            clearTimeout(saveTimers[key]);
        }
        saveTimers[key] = setTimeout(function () {
            delete saveTimers[key];
            persistRow(row);
        }, 600);
    }

    function flushPersistRow(row) {
        var key = row.dataset.id || row.dataset.rowKey;
        if (saveTimers[key]) {
            clearTimeout(saveTimers[key]);
            delete saveTimers[key];
        }

        return persistRow(row, { keepalive: true });
    }

    function flushAllPendingRows(options) {
        options = options || {};

        Object.keys(saveTimers).forEach(function (key) {
            clearTimeout(saveTimers[key]);
            delete saveTimers[key];
        });

        if (typeof window.syncAllFaqAnswerEditors === 'function') {
            window.syncAllFaqAnswerEditors(rowsEl);
        }

        var promises = allRows().map(function (row) {
            return persistRow(row, { keepalive: !!options.keepalive }) || Promise.resolve();
        });

        return Promise.all(promises);
    }

    function persistRow(row, options) {
        options = options || {};
        var data = rowPayload(row);
        if (!data.question && !data.answer) {
            return Promise.resolve();
        }
        if (!data.question || !data.answer) {
            return Promise.resolve();
        }

        if (row.dataset.id) {
            return request('PUT', cfg.updateBase + '/' + row.dataset.id, data, options).then(function (res) {
                if (res.success) {
                    row.dataset.origQ = data.question;
                    row.dataset.origA = data.answer;
                }
                return res;
            });
        }

        if (row.dataset.saving === '1') {
            return Promise.resolve();
        }

        row.dataset.saving = '1';
        return request('POST', cfg.storeUrl, data, options).then(function (res) {
            row.dataset.saving = '0';
            if (res.success && res.data && res.data.id) {
                var expanded = row.querySelector('.faq-row-body').classList.contains('show');
                var next = row.nextElementSibling;
                var idx = rowIndex(row);
                window.destroyFaqAnswerEditorForRow(row);
                row.remove();
                var saved = buildRow(res.data, idx, expanded);
                if (next) {
                    rowsEl.insertBefore(saved, next);
                } else {
                    rowsEl.appendChild(saved);
                }
                reindexRows();
                initSortable();
                persistReorder();
                scheduleEditorRefresh(120);
            }
            return res;
        }).catch(function () {
            row.dataset.saving = '0';
            return null;
        });
    }

    function persistReorder() {
        var ids = allRows()
            .filter(function (r) {
                return r.dataset.id;
            })
            .map(function (r) {
                return parseInt(r.dataset.id, 10);
            });
        if (!ids.length) {
            return;
        }
        request('POST', cfg.reorderUrl, { ids: ids }).catch(function () {
            loadFaqs();
        });
    }

    function bindRowEvents(row) {
        var qInput = row.querySelector('.faq-question-input');

        qInput.addEventListener('input', function () {
            updateSummary(row);
            schedulePersist(row);
        });

        row.querySelector('.faq-active-toggle').addEventListener('change', function () {
            if (!row.dataset.id) {
                schedulePersist(row);
                return;
            }

            var data = rowPayload(row);
            if (!data.question) {
                data.question = row.dataset.origQ || data.question;
            }
            if (!data.answer) {
                data.answer = row.dataset.origA || data.answer;
            }

            request('PUT', cfg.updateBase + '/' + row.dataset.id, data).then(function (res) {
                if (!res.success) {
                    row.querySelector('.faq-active-toggle').checked = !row.querySelector('.faq-active-toggle').checked;
                } else {
                    row.dataset.origQ = data.question;
                    row.dataset.origA = data.answer;
                }
            });
        });
    }

    function scheduleEditorRefresh(delayMs) {
        var delay = typeof delayMs === 'number' ? delayMs : 80;

        setTimeout(function () {
            var rows = allRows().filter(function (row) {
                return window.isFaqAnswerEditorVisible(row) || row.dataset.faqEditorPending === '1';
            });

            rows.forEach(function (row, index) {
                setTimeout(function () {
                    ensureAnswerEditor(row);
                }, index * 60);
            });
        }, delay);
    }

    function renderRows(items) {
        rowsEl.innerHTML = '';
        if (!items || !items.length) {
            appendDefaultRow(true);
            scheduleEditorRefresh(80);
            return;
        }

        items.forEach(function (item, i) {
            rowsEl.appendChild(buildRow(item, i, i === 0));
        });
        reindexRows();
        initSortable();
        scheduleEditorRefresh(120);
    }

    function loadFaqs() {
        showLoading(true);
        destroyAllAnswerEditors();
        rowsEl.innerHTML = '';
        request('GET', cfg.indexUrl)
            .then(function (res) {
                showLoading(false);
                renderRows(res.success ? (res.data || []) : []);
            })
            .catch(function () {
                showLoading(false);
                appendDefaultRow(true);
                scheduleEditorRefresh(80);
            });
    }

    function initSortable() {
        if (typeof Sortable === 'undefined') {
            return;
        }
        if (sortableInst) {
            sortableInst.destroy();
        }
        sortableInst = new Sortable(rowsEl, {
            animation: 150,
            handle: '.faq-row-handle',
            draggable: '.faq-row',
            onEnd: function () {
                reindexRows();
                persistReorder();
            },
        });
    }

    rowsEl.addEventListener('show.bs.collapse', function (e) {
        var row = e.target.closest && e.target.closest('.faq-row');
        if (row) {
            var ch = row.querySelector('.faq-row-chevron');
            if (ch) {
                ch.classList.add('rotated');
            }
        }
    });

    rowsEl.addEventListener('shown.bs.collapse', function (e) {
        var row = e.target.closest && e.target.closest('.faq-row');
        if (row) {
            ensureAnswerEditor(row);
        }
    });

    rowsEl.addEventListener('hide.bs.collapse', function (e) {
        var row = e.target.closest && e.target.closest('.faq-row');
        if (row) {
            var ch = row.querySelector('.faq-row-chevron');
            if (ch) {
                ch.classList.remove('rotated');
            }
        }
    });

    rowsEl.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.btn-faq-remove');
        if (!removeBtn || removeBtn.disabled) {
            return;
        }

        var row = removeBtn.closest('.faq-row');
        if (!row || allRows().length <= 1) {
            return;
        }

        if (row.dataset.id) {
            if (!confirm('Delete this FAQ item?')) {
                return;
            }
            removeBtn.disabled = true;
            request('DELETE', cfg.deleteBase + '/' + row.dataset.id).then(function (res) {
                if (res.success) {
                    window.destroyFaqAnswerEditorForRow(row);
                    row.remove();
                    ensureAtLeastOneRow();
                    reindexRows();
                    initSortable();
                } else {
                    alert(res.message || 'Error deleting FAQ.');
                    removeBtn.disabled = false;
                }
            });
            return;
        }

        window.destroyFaqAnswerEditorForRow(row);
        row.remove();
        ensureAtLeastOneRow();
        reindexRows();
    });

    card.querySelector('.faq-btn-add-row').addEventListener('click', function () {
        appendDefaultRow(true);
        scheduleEditorRefresh(80);
        var row = allRows()[allRows().length - 1];
        if (row) {
            row.querySelector('.faq-question-input').focus();
        }
    });

    function runFaqGenerate(replaceExisting) {
        var btn = card.querySelector('.faq-btn-generate');
        if (btn) {
            btn.disabled = true;
        }

        request('POST', cfg.generateUrl, { replace: replaceExisting ? 1 : 0 })
            .then(function (res) {
                if (btn) {
                    btn.disabled = false;
                }
                if (res.success) {
                    loadFaqs();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'FAQs Generated',
                            text: res.message || 'FAQs created from page content.',
                            timer: 2200,
                            showConfirmButton: false,
                        });
                    }
                    return;
                }
                var msg = res.message || 'Could not generate FAQs.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Generation failed', text: msg });
                } else {
                    alert(msg);
                }
            })
            .catch(function () {
                if (btn) {
                    btn.disabled = false;
                }
                alert('Could not generate FAQs. Please try again.');
            });
    }

    card.querySelector('.faq-btn-generate').addEventListener('click', function () {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Generate FAQs from content?',
                text: 'Builds questions from your saved title, excerpt, headings, and body text. No AI is used.',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Append new FAQs',
                denyButtonText: 'Replace all FAQs',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    runFaqGenerate(false);
                }
                if (result.isDenied) {
                    runFaqGenerate(true);
                }
            });
            return;
        }

        var replaceAll = confirm('Replace all existing FAQs?\n\nOK = Replace all\nCancel = Append new only');
        if (confirm('Generate FAQs from saved page content?')) {
            runFaqGenerate(replaceAll);
        }
    });

    card.querySelector('.faq-btn-expand-all').addEventListener('click', function () {
        rowsEl.querySelectorAll('.faq-row-body').forEach(function (body) {
            if (typeof bootstrap !== 'undefined') {
                bootstrap.Collapse.getOrCreateInstance(body, { toggle: false }).show();
            } else {
                body.classList.add('show');
            }
        });
        scheduleEditorRefresh(350);
    });

    card.querySelector('.faq-btn-collapse-all').addEventListener('click', function () {
        rowsEl.querySelectorAll('.faq-row-body').forEach(function (body) {
            if (typeof bootstrap !== 'undefined') {
                bootstrap.Collapse.getOrCreateInstance(body, { toggle: false }).hide();
            } else {
                body.classList.remove('show');
            }
        });
    });

    loadFaqs();

    window.flushAllFaqSectionSaves = function (options) {
        return flushAllPendingRows(options || {});
    };

    var parentForm = card.closest('form');
    if (parentForm && !parentForm.dataset.faqSectionFlushBound) {
        parentForm.dataset.faqSectionFlushBound = '1';
        parentForm.addEventListener('submit', function () {
            flushAllPendingRows({ keepalive: true });
            if (window.tinymce) {
                tinymce.triggerSave();
            }
        });
    }

    window.addEventListener('beforeunload', function () {
        flushAllPendingRows({ keepalive: true });
    });
};
