/**
 * WordPress-style Quick Edit + Duplicate on post list DataTables.
 */
window.initPostListQuickEdit = function (tableSelector, options) {
    if (!window.jQuery) {
        return;
    }

    options = options || {};
    var $ = window.jQuery;
    var table = $(tableSelector);

    if (!table.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable(table)) {
        return;
    }

    var dt = table.DataTable();
    var openPostId = null;

    function urlFromTemplate(template, postId) {
        return String(template || '').replace('__ID__', String(postId));
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initQuickEditTags(panel, initialTags) {
        var tagInput = panel.find('.post-qe-tag-input')[0];
        var suggestions = panel.find('.post-qe-tag-suggestions')[0];
        var selectedWrap = panel.find('.post-qe-selected-tags')[0];
        if (!tagInput || !selectedWrap) {
            return;
        }

        var selectedTags = {};
        var suggestTimeout = null;

        function addTag(name) {
            name = String(name || '').trim();
            if (!name) {
                return;
            }
            var key = name.toLowerCase();
            if (selectedTags[key]) {
                return;
            }
            selectedTags[key] = name;

            var badge = document.createElement('span');
            badge.className = 'badge rounded-pill text-bg-primary d-inline-flex align-items-center gap-1 me-1 mb-1 px-2 py-1';
            badge.dataset.tagName = name;

            var text = document.createTextNode(name + ' ');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-close btn-close-white';
            btn.style.fontSize = '.55rem';
            btn.setAttribute('aria-label', 'Remove ' + name);
            btn.addEventListener('click', function () {
                delete selectedTags[key];
                badge.remove();
            });

            badge.appendChild(text);
            badge.appendChild(btn);
            selectedWrap.appendChild(badge);
        }

        (initialTags || []).forEach(function (tag) {
            var name = typeof tag === 'string' ? tag : tag.name;
            addTag(name);
        });

        tagInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                String(this.value).split(',').forEach(function (part) {
                    addTag(part);
                });
                this.value = '';
                hideSuggestions();
            }
        });

        tagInput.addEventListener('input', function () {
            var q = this.value.trim();
            clearTimeout(suggestTimeout);
            if (q.length < 1 || !options.tagSuggestUrl) {
                hideSuggestions();
                return;
            }
            suggestTimeout = setTimeout(function () {
                fetch(options.tagSuggestUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.json(); })
                    .then(function (tags) {
                        if (!Array.isArray(tags) || !tags.length) {
                            hideSuggestions();
                            return;
                        }
                        suggestions.innerHTML = tags.map(function (t) {
                            return '<div class="post-qe-tag-suggestion-item px-3 py-2 border-bottom" data-tag-name="'
                                + escapeHtml(t.name) + '">' + escapeHtml(t.name) + '</div>';
                        }).join('');
                        suggestions.classList.remove('d-none');
                    })
                    .catch(function () { hideSuggestions(); });
            }, 250);
        });

        suggestions.addEventListener('click', function (e) {
            var item = e.target.closest('[data-tag-name]');
            if (!item) {
                return;
            }
            addTag(item.getAttribute('data-tag-name'));
            tagInput.value = '';
            hideSuggestions();
        });

        document.addEventListener('click', function onDocClick(e) {
            if (!panel[0].contains(e.target)) {
                hideSuggestions();
            }
        });

        function hideSuggestions() {
            suggestions.innerHTML = '';
            suggestions.classList.add('d-none');
        }

        panel.data('collectTags', function () {
            return Object.values(selectedTags);
        });
    }

    function collectTagNames(panel) {
        var collector = panel.data('collectTags');
        if (typeof collector === 'function') {
            return collector();
        }
        return [];
    }

    function renderQuickEditPanel(data) {
        var post = data.post;
        var statusOptions = Object.keys(data.statuses || {}).map(function (key) {
            return '<option value="' + escapeHtml(key) + '"' + (key === post.status ? ' selected' : '') + '>'
                + escapeHtml(data.statuses[key]) + '</option>';
        }).join('');

        var categoryChecks = (data.categories || []).map(function (cat) {
            var checked = (post.category_ids || []).indexOf(cat.id) !== -1 ? ' checked' : '';
            var indent = cat.parent_id ? ' ps-3' : '';
            return '<label class="form-check small mb-1' + indent + '">'
                + '<input class="form-check-input" type="checkbox" name="category_ids[]" value="' + cat.id + '"' + checked + '> '
                + escapeHtml(cat.name)
                + '</label>';
        }).join('');

        return ''
            + '<div class="post-quick-edit-panel p-3 bg-light border-top" data-post-id="' + post.id + '">'
            + '<div class="d-flex align-items-center justify-content-between mb-3">'
            + '<strong class="text-uppercase small text-muted">Quick Edit</strong>'
            + '<button type="button" class="btn btn-sm btn-link text-muted btn-post-quick-edit-cancel">Cancel</button>'
            + '</div>'
            + '<form class="post-quick-edit-form">'
            + '<div class="row g-3">'
            + '<div class="col-lg-4">'
            + '<label class="form-label small">Title</label>'
            + '<input type="text" class="form-control form-control-sm" name="title" value="' + escapeHtml(post.title) + '" required>'
            + '<label class="form-label small mt-2">Slug</label>'
            + '<input type="text" class="form-control form-control-sm" name="slug" value="' + escapeHtml(post.slug) + '">'
            + '<label class="form-label small mt-2">Excerpt</label>'
            + '<textarea class="form-control form-control-sm" name="excerpt" rows="3">' + escapeHtml(post.excerpt || '') + '</textarea>'
            + '</div>'
            + '<div class="col-lg-3">'
            + '<label class="form-label small">Categories</label>'
            + '<div class="post-quick-edit-categories border rounded bg-white p-2" style="max-height:180px;overflow:auto">' + categoryChecks + '</div>'
            + '</div>'
            + '<div class="col-lg-5">'
            + '<label class="form-label small">Tags</label>'
            + '<div class="post-quick-edit-tags position-relative">'
            + '<input type="text" class="form-control form-control-sm post-qe-tag-input" placeholder="Add tag, press Enter or comma…" autocomplete="off">'
            + '<div class="post-qe-tag-suggestions d-none position-absolute w-100 border rounded bg-white shadow-sm" style="z-index:1050;top:100%;max-height:160px;overflow-y:auto;"></div>'
            + '<div class="post-qe-selected-tags d-flex flex-wrap gap-1 mt-2 min-height-30"></div>'
            + '<p class="form-text mb-0 small">Type and press Enter or comma. Pick from suggestions.</p>'
            + '</div>'
            + '<div class="row g-2 mt-2">'
            + '<div class="col-md-6">'
            + '<label class="form-label small">Status</label>'
            + '<select class="form-select form-select-sm" name="status">' + statusOptions + '</select>'
            + '</div>'
            + '</div>'
            + '<div class="d-flex gap-2 mt-3">'
            + '<button type="submit" class="btn btn-sm btn-primary">Update</button>'
            + '<button type="button" class="btn btn-sm btn-outline-secondary btn-post-quick-edit-cancel">Cancel</button>'
            + '<span class="post-quick-edit-status small text-muted align-self-center ms-2"></span>'
            + '</div>'
            + '</div>'
            + '</div>'
            + '</form>'
            + '</div>';
    }

    function closeQuickEdit() {
        if (openPostId === null) {
            return;
        }
        dt.rows().every(function () {
            var data = this.data();
            if (data && String(data.id) === String(openPostId)) {
                this.child.hide();
            }
        });
        openPostId = null;
    }

    function openQuickEdit(postId, tr) {
        var row = dt.row(tr);
        if (openPostId === String(postId)) {
            closeQuickEdit();
            return;
        }

        closeQuickEdit();

        fetch(urlFromTemplate(options.quickEditShowUrlTemplate, postId), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, payload: payload };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.success) {
                    throw new Error((result.payload && result.payload.message) || 'Could not load quick edit.');
                }
                row.child(renderQuickEditPanel(result.payload)).show();
                var panel = $(row.child()[0]).find('.post-quick-edit-panel');
                initQuickEditTags(panel, result.payload.post.tags);
                openPostId = String(postId);
                tr.addClass('post-quick-edit-open');
            })
            .catch(function (error) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Quick Edit', text: error.message || 'Request failed.' });
                } else {
                    window.alert(error.message || 'Request failed.');
                }
            });
    }

    table.on('click', '.btn-post-quick-edit', function (event) {
        event.preventDefault();
        var postId = $(this).data('post-id');
        openQuickEdit(postId, $(this).closest('tr'));
    });

    table.on('click', '.btn-post-quick-edit-cancel', function () {
        closeQuickEdit();
        table.find('tr.post-quick-edit-open').removeClass('post-quick-edit-open');
    });

    table.on('submit', '.post-quick-edit-form', function (event) {
        event.preventDefault();
        var panel = $(this).closest('.post-quick-edit-panel');
        var postId = panel.data('post-id');
        var statusEl = panel.find('.post-quick-edit-status');
        var form = this;
        var formData = new FormData(form);

        var categoryIds = [];
        panel.find('input[name="category_ids[]"]:checked').each(function () {
            categoryIds.push(parseInt(this.value, 10));
        });

        var payload = {
            title: formData.get('title'),
            slug: formData.get('slug'),
            status: formData.get('status'),
            excerpt: formData.get('excerpt'),
            tag_names: collectTagNames(panel),
            category_ids: categoryIds,
        };

        statusEl.text('Saving…');

        fetch(urlFromTemplate(options.quickEditUpdateUrlTemplate, postId), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': options.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (body) {
                    return { ok: response.ok, payload: body };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    throw new Error((result.payload && result.payload.message) || 'Update failed.');
                }
                if (typeof window.showSuccessToast === 'function') {
                    window.showSuccessToast(result.payload.message || 'Post updated.');
                }
                closeQuickEdit();
                table.find('tr.post-quick-edit-open').removeClass('post-quick-edit-open');
                dt.ajax.reload(null, false);
            })
            .catch(function (error) {
                statusEl.text('');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Quick Edit', text: error.message || 'Update failed.' });
                } else {
                    window.alert(error.message || 'Update failed.');
                }
            });
    });

    if (options.duplicateUrlTemplate) {
        table.on('click', '.btn-post-duplicate', function (event) {
            event.preventDefault();
            var button = $(this);
            var postId = button.data('post-id');
            var itemName = button.data('item-name') || 'this post';

            var runDuplicate = function () {
                fetch(urlFromTemplate(options.duplicateUrlTemplate, postId), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': options.csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        return response.json().then(function (body) {
                            return { ok: response.ok, payload: body };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.payload || !result.payload.success) {
                            throw new Error((result.payload && result.payload.message) || 'Duplicate failed.');
                        }
                        if (result.payload.edit_url) {
                            window.location.href = result.payload.edit_url;
                        }
                    })
                    .catch(function (error) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Duplicate', text: error.message || 'Request failed.' });
                        } else {
                            window.alert(error.message || 'Request failed.');
                        }
                    });
            };

            if (typeof Swal === 'undefined') {
                if (window.confirm('Duplicate "' + itemName + '" with all content, SEO, and FAQs?')) {
                    runDuplicate();
                }
                return;
            }

            Swal.fire({
                title: 'Duplicate post?',
                text: 'Create a draft copy of "' + itemName + '" including SEO, FAQs, categories, and tags.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Duplicate',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-outline-secondary',
                },
                buttonsStyling: false,
            }).then(function (result) {
                if (result.isConfirmed) {
                    runDuplicate();
                }
            });
        });
    }

    table.on('draw.dt', function () {
        closeQuickEdit();
        table.find('tr.post-quick-edit-open').removeClass('post-quick-edit-open');
    });
};
