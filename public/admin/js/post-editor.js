/**
 * WordPress-style debounced autosave + edit locking for blog/case study post forms.
 */
(function () {
    'use strict';

    function formatSavedTime(isoString) {
        if (!isoString) {
            return '';
        }
        var date = new Date(isoString);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function PostEditor(config) {
        this.config = config || {};
        this.form = document.getElementById(config.formId);
        this.postId = config.postId || null;
        this.isCreate = !this.postId;
        this.hasLock = false;
        this.isReadOnly = false;
        this.autosaveTimer = null;
        this.heartbeatTimer = null;
        this.isSaving = false;
        this.pendingSave = false;
        this.lastSnapshot = '';
        this.allowLeave = false;
        this.leaveConfirmOpen = false;
        this.sessionKeepAliveTimer = null;
        this.isRefreshingSession = false;
        this.currentLock = null;
        this.hadLockBefore = false;
        this.lockLostNotified = false;
        this.lockCheckTimer = null;
        this.isAcquiringLock = false;

        this.statusEl = document.getElementById('postEditorAutosaveStatus');
        this.lockBannerEl = document.getElementById('postEditorLockBanner');
        this.lockBannerTextEl = document.getElementById('postEditorLockBannerText');
        this.takeOverBtn = document.getElementById('postEditorTakeOverBtn');
    }

    PostEditor.prototype.init = function () {
        if (!this.form) {
            return;
        }

        this.ensureDraftStatusOnCreate();
        this.bindChangeListeners();
        this.bindManualSubmit();
        this.bindLeaveGuard();
        this.bindTakeOver();
        this.startSessionKeepAlive();

        // Edit: baseline snapshot so loaded content is not treated as dirty.
        // Create: empty baseline until first successful autosave.
        this.lastSnapshot = this.postId ? this.buildSnapshot() : '';

        // Drop any leftover local backups from older builds (restore feature removed).
        this.clearStaleLocalDraftKeys();

        if (this.postId) {
            var takeover = new URLSearchParams(window.location.search).get('takeover') === '1';
            if (takeover) {
                window.history.replaceState({}, '', window.location.pathname);
                this.acquireLock(true);
            } else {
                this.acquireLock(false);
            }
            this.startLockWatchers();
        }

        window.addEventListener('pagehide', this.onPageHide.bind(this));
        document.addEventListener('visibilitychange', this.onVisibilityChange.bind(this));
    };

    PostEditor.prototype.ensureDraftStatusOnCreate = function () {
        if (!this.isCreate) {
            return;
        }

        var status = this.form.querySelector('#status');
        if (status && !status.value) {
            status.value = 'draft';
        }
    };

    PostEditor.prototype.bindChangeListeners = function () {
        var self = this;

        this.form.addEventListener('input', function () {
            self.scheduleAutosave();
        });

        this.form.addEventListener('change', function () {
            self.scheduleAutosave();
        });

        // Select2 / taxonomy widgets
        if (window.jQuery) {
            window.jQuery(this.form).on('change.select2', 'select', function () {
                self.scheduleAutosave();
            });
        }

        // Programmatic media / FAQ / meta updates
        document.addEventListener('post-editor:changed', function () {
            self.scheduleAutosave();
        });

        if (window.tinymce) {
            window.tinymce.on('AddEditor', function (event) {
                event.editor.on('change keyup undo redo SetContent', function () {
                    self.scheduleAutosave();
                });
            });

            // Editors already on the page when this script boots
            if (window.tinymce.editors && window.tinymce.editors.forEach) {
                window.tinymce.editors.forEach(function (editor) {
                    editor.on('change keyup undo redo SetContent', function () {
                        self.scheduleAutosave();
                    });
                });
            }
        }
    };

    PostEditor.prototype.bindManualSubmit = function () {
        var self = this;

        this.form.addEventListener('submit', function () {
            self.allowLeave = true;
            self.clearAutosaveTimer();
            self.applyCsrfToken(self.currentCsrfToken());
            if (window.tinymce) {
                window.tinymce.triggerSave();
            }
            self.ensureUpdateFormAction();
        });
    };

    PostEditor.prototype.bindLeaveGuard = function () {
        var self = this;

        // Tab close / browser navigation: browsers only allow the native prompt.
        window.addEventListener('beforeunload', function (event) {
            if (self.allowLeave || self.isReadOnly || !self.isDirty()) {
                return;
            }

            event.preventDefault();
            event.returnValue = 'You have unsaved changes. Save your draft before leaving.';
        });

        // Keyboard refresh (F5 / Ctrl+R): show the same custom Save & leave popup.
        document.addEventListener('keydown', function (event) {
            if (self.allowLeave || self.isReadOnly || self.leaveConfirmOpen || !self.isDirty()) {
                return;
            }

            var key = event.key || '';
            var isRefresh = key === 'F5' || ((event.ctrlKey || event.metaKey) && (key === 'r' || key === 'R'));
            if (!isRefresh) {
                return;
            }

            event.preventDefault();
            self.confirmLeave(window.location.href, { reload: true });
        }, true);

        document.addEventListener('click', function (event) {
            if (self.allowLeave || self.isReadOnly || self.leaveConfirmOpen || !self.isDirty()) {
                return;
            }

            var link = event.target.closest('a[href]');
            if (!link) {
                return;
            }

            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
                return;
            }

            if (link.target === '_blank' || link.hasAttribute('download') || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            try {
                var url = new URL(href, window.location.href);
                if (url.origin === window.location.origin
                    && url.pathname === window.location.pathname
                    && url.search === window.location.search) {
                    return;
                }
            } catch (error) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            self.confirmLeave(href);
        }, true);
    };

    PostEditor.prototype.isDirty = function () {
        if (this.isReadOnly) {
            return false;
        }

        if (this.autosaveTimer || this.isSaving || this.pendingSave) {
            // Pending timer on an empty create form is not real dirty content.
            if (this.isCreate && !this.formHasMeaningfulContent()) {
                return false;
            }
            return true;
        }

        if (this.isCreate) {
            if (!this.formHasMeaningfulContent()) {
                return false;
            }
            return this.buildSnapshot() !== this.lastSnapshot;
        }

        if (!this.formHasContent()) {
            return false;
        }

        return this.buildSnapshot() !== this.lastSnapshot;
    };

    /**
     * @param {string} href
     * @param {{reload?: boolean}} [options]
     */
    PostEditor.prototype.confirmLeave = function (href, options) {
        var self = this;
        options = options || {};

        if (this.leaveConfirmOpen) {
            return;
        }

        this.leaveConfirmOpen = true;

        var leaveNow = function () {
            self.allowLeave = true;
            self.leaveConfirmOpen = false;
            if (options.reload) {
                window.location.reload();
                return;
            }
            window.location.href = href;
        };

        var stay = function () {
            self.leaveConfirmOpen = false;
        };

        var title = options.reload ? 'Reload this page?' : 'Unsaved changes';
        var body = options.reload
            ? '<p class="mb-2">You have edits that are not saved yet. Reloading will lose anything not autosaved.</p>'
                + '<p class="mb-0 text-muted small">Save your draft first, leave without saving, or stay on the page.</p>'
            : '<p class="mb-2">You have edits that are not saved yet (since the last autosave).</p>'
                + '<p class="mb-0 text-muted small">Save your draft before leaving, or leave without saving.</p>';

        if (typeof Swal === 'undefined') {
            if (window.confirm(body.replace(/<[^>]+>/g, ' '))) {
                leaveNow();
            } else {
                stay();
            }
            return;
        }

        Swal.fire({
            title: title,
            html: body,
            icon: 'warning',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: options.reload ? 'Save & reload' : 'Save & leave',
            denyButtonText: options.reload ? 'Reload without saving' : 'Leave without saving',
            cancelButtonText: 'Stay on page',
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                denyButton: 'btn btn-outline-danger me-2',
                cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
        }).then(function (result) {
            if (result.isConfirmed) {
                self.runAutosave(true).then(function (ok) {
                    if (ok) {
                        leaveNow();
                    } else {
                        stay();
                        self.setStatus('error', 'Could not save. Stay on the page and try again.');
                    }
                });
                return;
            }

            if (result.isDenied) {
                leaveNow();
                return;
            }

            stay();
        });
    };

    /**
     * Guard: never submit PUT/PATCH to the GET-only /edit page URL.
     */
    PostEditor.prototype.ensureUpdateFormAction = function () {
        var action = this.form.getAttribute('action') || '';
        var methodField = this.form.querySelector('input[name="_method"]');
        var spoofedMethod = methodField ? String(methodField.value || '').toUpperCase() : '';

        if ((spoofedMethod === 'PUT' || spoofedMethod === 'PATCH') && /\/edit\/?$/.test(action)) {
            this.form.setAttribute('action', action.replace(/\/edit\/?$/, ''));
        }
    };

    PostEditor.prototype.bindTakeOver = function () {
        var self = this;

        if (!this.takeOverBtn) {
            return;
        }

        this.takeOverBtn.addEventListener('click', function (event) {
            event.preventDefault();
            self.confirmTakeOver();
        });
    };

    PostEditor.prototype.scheduleAutosave = function () {
        if (this.isReadOnly) {
            return;
        }

        // Create: ignore default-only forms (status=draft, empty FAQ rows, etc.).
        if (this.isCreate) {
            if (!this.formHasMeaningfulContent()) {
                return;
            }
        } else if (!this.formHasContent()) {
            return;
        }

        var self = this;
        this.clearAutosaveTimer();
        this.setStatus('pending', 'Unsaved changes');

        this.autosaveTimer = window.setTimeout(function () {
            self.runAutosave();
        }, this.config.debounceMs || 30000);
    };

    PostEditor.prototype.clearAutosaveTimer = function () {
        if (this.autosaveTimer) {
            window.clearTimeout(this.autosaveTimer);
            this.autosaveTimer = null;
        }
    };

    PostEditor.prototype.formHasContent = function () {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }

        var fields = this.form.querySelectorAll('input, textarea, select');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (!field.name || field.type === 'hidden' && field.name === '_token') {
                continue;
            }
            if (field.type === 'checkbox' || field.type === 'radio') {
                if (field.checked) {
                    return true;
                }
                continue;
            }
            if (field.type === 'file') {
                continue;
            }
            if (String(field.value || '').trim() !== '') {
                return true;
            }
        }

        return false;
    };

    /**
     * True only when the author entered real content (not default Draft/status/FAQ chrome).
     * Used so blank create pages do not trigger leave warnings.
     */
    PostEditor.prototype.formHasMeaningfulContent = function () {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }

        var fields = this.form.querySelectorAll('input, textarea, select');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (!field.name) {
                continue;
            }
            if (this.fieldHasMeaningfulValue(field.name, field)) {
                return true;
            }
        }

        return false;
    };

    PostEditor.prototype.isBlankRichText = function (value) {
        return String(value || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/&nbsp;/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim() === '';
    };

    /**
     * @param {string} name
     * @param {HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement|{value?: string, type?: string, checked?: boolean}} field
     */
    PostEditor.prototype.fieldHasMeaningfulValue = function (name, field) {
        var value = field && field.value != null ? String(field.value) : '';
        var type = field && field.type ? field.type : 'text';
        var checked = !!(field && field.checked);

        // Defaults that must never count as "user content" on create.
        if (
            name === '_token'
            || name === '_method'
            || name === 'autosave'
            || name === 'status'
            || name === 'sort_order'
            || name === 'published_at'
            || name === 'is_featured'
            || name === 'allow_comments'
        ) {
            return false;
        }

        // FAQ active toggles / empty FAQ scaffolding.
        if (/^faqs\[\d+]\[is_active]$/.test(name)) {
            return false;
        }
        if (/^faqs\[\d+]\[id]$/.test(name)) {
            return false;
        }

        if (type === 'file') {
            return false;
        }

        if (name.indexOf('category_ids') === 0 || name.indexOf('tag_ids') === 0 || name.indexOf('cs_taxonomy_term_ids') === 0) {
            return type === 'checkbox' || type === 'radio' ? checked : value.trim() !== '';
        }

        if (name.indexOf('tag_names') === 0) {
            return value.trim() !== '';
        }

        if (/^faqs\[\d+]\[(question|answer)]$/.test(name)) {
            return !this.isBlankRichText(value);
        }

        if (name.indexOf('results[') === 0) {
            return value.trim() !== '';
        }

        var meaningfulExact = {
            title: true,
            slug: true,
            excerpt: true,
            content: true,
            article_end_content: true,
            featured_image_media_id: true,
            featured_image_thumb_media_id: true,
            client_name: true,
            client_logo_media_id: true,
            client_website_url: true,
            challenge: true,
            solution: true,
            benefits: true,
            testimonial_quote: true,
            testimonial_author_name: true,
            testimonial_author_title: true,
            extra_meta: true,
            seo_title: true,
            seo_description: true,
            seo_keywords: true,
            og_title: true,
            og_description: true,
            twitter_title: true,
            twitter_description: true,
            canonical_url: true,
            og_image_media_id: true,
            twitter_image_media_id: true,
        };

        if (!meaningfulExact[name]) {
            return false;
        }

        if (type === 'checkbox' || type === 'radio') {
            return checked;
        }

        return !this.isBlankRichText(value);
    };

    PostEditor.prototype.buildAutosaveFormData = function () {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }

        var data = new FormData(this.form);
        data.delete('_method');
        data.set('autosave', '1');
        data.set('_token', this.currentCsrfToken());

        return data;
    };

    PostEditor.prototype.buildSnapshot = function () {
        var data = this.buildAutosaveFormData();
        var parts = [];
        data.forEach(function (value, key) {
            parts.push(key + '=' + String(value));
        });
        parts.sort();

        return parts.join('&');
    };

    /**
     * @param {boolean} [force]
     * @param {boolean} [retried]
     * @returns {Promise<boolean>}
     */
    PostEditor.prototype.runAutosave = function (force, retried) {
        var self = this;

        if (this.isReadOnly) {
            return Promise.resolve(false);
        }

        if (this.isSaving) {
            this.pendingSave = true;
            return Promise.resolve(false);
        }

        if (!this.formHasContent() && !force) {
            return Promise.resolve(true);
        }

        if (this.isCreate && !this.formHasMeaningfulContent() && !force) {
            return Promise.resolve(true);
        }

        var snapshot = this.buildSnapshot();
        if (!force && snapshot === this.lastSnapshot) {
            return Promise.resolve(true);
        }

        var url = this.postId ? this.config.autosaveUrl : this.config.autosaveStoreUrl;

        if (!url) {
            return Promise.resolve(false);
        }

        this.clearAutosaveTimer();
        this.isSaving = true;
        this.setStatus('saving', 'Saving draft…');

        var data = this.buildAutosaveFormData();

        if (this.isCreate) {
            var statusField = this.form.querySelector('#status');
            if (statusField) {
                data.set('status', 'draft');
            }
        }

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.currentCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: data,
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, status: response.status, payload: payload };
                }).catch(function () {
                    return { ok: response.ok, status: response.status, payload: { message: 'Autosave failed.' } };
                });
            })
            .then(function (result) {
                if (result.status === 419 && !retried) {
                    // Outer finally must not clear isSaving while the retry is in flight.
                    self._autosaveRetrying = true;
                    return self.refreshSession().then(function (ok) {
                        if (!ok) {
                            throw new Error('Session expired. Please refresh and log in again.');
                        }
                        self.isSaving = false;
                        return self.runAutosave(force, true);
                    });
                }

                if (!result.ok) {
                    var message = (result.payload && result.payload.message) || 'Autosave failed.';
                    if (result.payload && result.payload.errors) {
                        var firstKey = Object.keys(result.payload.errors)[0];
                        if (firstKey && result.payload.errors[firstKey] && result.payload.errors[firstKey][0]) {
                            message = result.payload.errors[firstKey][0];
                        }
                    }
                    if (result.status === 419) {
                        message = 'Session expired. Please refresh and log in again.';
                    }
                    throw new Error(message);
                }

                self.lastSnapshot = self.buildSnapshot();
                self.onAutosaveSuccess(result.payload);
                return true;
            })
            .catch(function (error) {
                self.setStatus('error', error.message || 'Unable to save draft.');
                return false;
            })
            .finally(function () {
                if (self._autosaveRetrying) {
                    self._autosaveRetrying = false;
                    return;
                }
                self.isSaving = false;
                if (self.pendingSave) {
                    self.pendingSave = false;
                    self.scheduleAutosave();
                }
            });
    };

    PostEditor.prototype.onAutosaveSuccess = function (payload) {
        this.setStatus('saved', 'Draft saved at ' + formatSavedTime(payload.saved_at));

        if (!this.postId && payload.post_id) {
            this.promoteCreateToEdit(payload);
        }
    };

    PostEditor.prototype.promoteCreateToEdit = function (payload) {
        this.postId = payload.post_id;
        this.isCreate = false;
        this.config.autosaveUrl = payload.autosave_url;
        this.config.lockUrl = payload.lock_url;
        this.config.lockHeartbeatUrl = payload.lock_heartbeat_url;
        this.config.lockReleaseUrl = payload.lock_release_url;
        this.config.lockCheckUrl = payload.lock_check_url;
        this.config.showLockAlertOnLoad = false;

        // Form must POST/PUT to the update route (…/posts/{id}), not the edit page (…/posts/{id}/edit).
        var updateUrl = payload.update_url || this.deriveUpdateUrlFromEditUrl(payload.edit_url);
        if (updateUrl) {
            this.form.setAttribute('action', updateUrl);
        }
        this.ensureMethodField('PUT');

        if (payload.edit_url) {
            window.history.replaceState({}, '', payload.edit_url);
        }

        this.acquireLock(false);
        this.startLockWatchers();
        this.lastSnapshot = this.buildSnapshot();
    };

    PostEditor.prototype.ensureMethodField = function (method) {
        var existing = this.form.querySelector('input[name="_method"]');
        if (existing) {
            existing.value = method;
            return;
        }

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_method';
        input.value = method;
        this.form.appendChild(input);
    };

    /**
     * Laravel edit URLs end with /edit; update URLs are the same path without that suffix.
     */
    PostEditor.prototype.deriveUpdateUrlFromEditUrl = function (editUrl) {
        if (!editUrl || typeof editUrl !== 'string') {
            return null;
        }

        return editUrl.replace(/\/edit\/?$/, '');
    };

    PostEditor.prototype.acquireLock = function (force) {
        var self = this;

        if (!this.config.lockUrl || !this.postId || this.isAcquiringLock) {
            return;
        }

        this.isAcquiringLock = true;

        fetchJson(this.config.lockUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.currentCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: (function () {
                var body = new FormData();
                body.append('force', force ? '1' : '0');
                body.append('_token', self.currentCsrfToken());
                return body;
            })(),
            credentials: 'same-origin',
        })
            .then(function (result) {
                if (result.status === 423 || (result.payload && result.payload.is_locked)) {
                    self.showForeignLock(result.payload.lock || result.payload, !!self.config.showLockAlertOnLoad);
                    self.config.showLockAlertOnLoad = false;
                    return;
                }

                if (!result.ok) {
                    if (result.status === 419) {
                        self.showLockNotice('Your session expired. Please refresh the page.');
                        return;
                    }

                    self.retryAcquireLock(force);
                    return;
                }

                self.hasLock = true;
                self.hadLockBefore = true;
                self.isReadOnly = false;
                self.lockLostNotified = false;
                self.currentLock = result.payload.lock || null;
                self.hideForeignLock();
                self.setFormDisabled(false);
                self.updateLockStatus();
                self.sendHeartbeat(false);
            })
            .catch(function () {
                self.retryAcquireLock(force);
            })
            .finally(function () {
                self.isAcquiringLock = false;
            });
    };

    PostEditor.prototype.retryAcquireLock = function (force) {
        var self = this;

        if (this._lockRetryScheduled) {
            return;
        }

        this._lockRetryScheduled = true;

        window.setTimeout(function () {
            self._lockRetryScheduled = false;
            if (!self.hasLock && !self.isReadOnly && self.postId) {
                self.acquireLock(force);
            }
        }, 1500);
    };

    PostEditor.prototype.startLockWatchers = function () {
        this.startHeartbeat();
        this.startLockPolling();
    };

    PostEditor.prototype.startLockPolling = function () {
        var self = this;

        if (!this.config.lockCheckUrl || !this.postId) {
            return;
        }

        if (this.lockCheckTimer) {
            window.clearInterval(this.lockCheckTimer);
        }

        this.lockCheckTimer = window.setInterval(function () {
            self.checkLockStatus();
        }, this.config.lockPollMs || 5000);
    };

    PostEditor.prototype.checkLockStatus = function () {
        var self = this;

        if (!this.config.lockCheckUrl || !this.postId || !this.hasLock || this.isReadOnly) {
            return;
        }

        fetchJson(this.config.lockCheckUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (result) {
                if (!result.ok) {
                    return;
                }

                if (result.payload.is_locked || (result.payload.lock && !result.payload.is_mine)) {
                    self.handleLockLost(result.payload.lock, true);
                }
            })
            .catch(function () {
                // Ignore transient polling errors.
            });
    };

    PostEditor.prototype.startHeartbeat = function () {
        var self = this;

        if (!this.config.lockHeartbeatUrl || !this.postId) {
            return;
        }

        if (this.heartbeatTimer) {
            window.clearInterval(this.heartbeatTimer);
        }

        this.heartbeatTimer = window.setInterval(function () {
            self.sendHeartbeat(false);
        }, this.config.heartbeatMs || 10000);
    };

    PostEditor.prototype.sendHeartbeat = function (fromVisibility) {
        var self = this;

        if (!this.config.lockHeartbeatUrl || !this.postId || !this.hasLock || this.isReadOnly) {
            return;
        }

        fetchJson(this.config.lockHeartbeatUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.currentCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (result) {
                if (result.status === 423 || (result.payload && (result.payload.is_locked || result.payload.taken_over))) {
                    self.handleLockLost(result.payload.lock || null, true);
                    return;
                }

                if (!result.ok && fromVisibility) {
                    self.checkLockStatus();
                }
            })
            .catch(function () {
                if (fromVisibility) {
                    self.checkLockStatus();
                }
            });
    };

    PostEditor.prototype.releaseLock = function () {
        if (!this.hasLock || !this.config.lockReleaseUrl) {
            return;
        }

        this.hasLock = false;

        fetch(this.config.lockReleaseUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.currentCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            keepalive: true,
            credentials: 'same-origin',
        }).catch(function () {
            // Best effort on unload.
        });
    };

    PostEditor.prototype.onPageHide = function () {
        this.releaseLock();
    };

    PostEditor.prototype.onVisibilityChange = function () {
        if (document.visibilityState === 'visible') {
            this.refreshSession();
            if (this.hasLock && !this.isReadOnly) {
                this.sendHeartbeat(true);
            }
        }
    };

    PostEditor.prototype.currentCsrfToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return this.config.csrfToken
            || (meta ? meta.getAttribute('content') : '')
            || '';
    };

    PostEditor.prototype.applyCsrfToken = function (token) {
        if (!token) {
            return;
        }

        this.config.csrfToken = token;

        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', token);
        }

        document.querySelectorAll('input[name="_token"]').forEach(function (input) {
            input.value = token;
        });
    };

    PostEditor.prototype.refreshSession = function () {
        var self = this;
        var url = this.config.sessionPingUrl;

        if (!url) {
            return Promise.resolve(false);
        }

        if (this.isRefreshingSession) {
            return Promise.resolve(true);
        }

        this.isRefreshingSession = true;

        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        })
            .then(function (response) {
                if (response.status === 401 || response.status === 419) {
                    return false;
                }
                return response.json().then(function (payload) {
                    if (payload && payload.csrf_token) {
                        self.applyCsrfToken(payload.csrf_token);
                    }
                    return !!(payload && payload.ok !== false && payload.csrf_token);
                }).catch(function () {
                    return response.ok;
                });
            })
            .catch(function () {
                return false;
            })
            .finally(function () {
                self.isRefreshingSession = false;
            });
    };

    PostEditor.prototype.startSessionKeepAlive = function () {
        var self = this;
        var interval = this.config.sessionKeepAliveMs || (5 * 60 * 1000);

        if (this.sessionKeepAliveTimer) {
            window.clearInterval(this.sessionKeepAliveTimer);
        }

        // Immediate touch so create pages (no edit lock) also keep the session warm.
        this.refreshSession();

        this.sessionKeepAliveTimer = window.setInterval(function () {
            self.refreshSession();
        }, interval);
    };

    /**
     * One-time cleanup of old post-editor-draft:* keys (feature removed).
     */
    PostEditor.prototype.clearStaleLocalDraftKeys = function () {
        try {
            var keys = [];
            for (var i = 0; i < window.localStorage.length; i++) {
                var key = window.localStorage.key(i);
                if (key && key.indexOf('post-editor-draft:') === 0) {
                    keys.push(key);
                }
            }
            keys.forEach(function (key) {
                window.localStorage.removeItem(key);
            });
        } catch (error) {
            // ignore
        }
    };

    PostEditor.prototype.handleLockLost = function (lock, showAlert) {
        if (this.isReadOnly && this.lockLostNotified) {
            return;
        }

        this.clearAutosaveTimer();
        this.hasLock = false;
        this.isReadOnly = true;
        this.currentLock = lock || null;
        this.setFormDisabled(true);
        this.updateLockStatus();

        if (this.lockBannerEl) {
            this.lockBannerEl.classList.remove('d-none');
        }

        if (this.lockBannerTextEl) {
            var name = (lock && lock.user_name) ? lock.user_name : 'Another user';
            this.lockBannerTextEl.textContent = name + ' has taken over editing. You cannot save changes.';
        }

        if (showAlert && !this.lockLostNotified) {
            this.lockLostNotified = true;
            this.showLockLostDialog(lock);
        }
    };

    PostEditor.prototype.showLockNotice = function (message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Edit lock',
                text: message,
                icon: 'warning',
                confirmButtonText: 'Refresh page',
                customClass: { confirmButton: 'btn btn-primary' },
                buttonsStyling: false,
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
            return;
        }

        window.alert(message);
    };

    PostEditor.prototype.showLockLostDialog = function (lock) {
        var self = this;
        var name = (lock && lock.user_name) ? lock.user_name : 'Another user';

        if (typeof Swal === 'undefined') {
            window.alert(name + ' has taken over editing this post. You cannot save changes.');
            return;
        }

        Swal.fire({
            title: 'Editing taken over',
            html: '<p class="mb-2"><strong>' + self.escapeHtml(name) + '</strong> has taken over editing this post.</p><p class="mb-0 text-muted small">You are now in read-only mode. Any unsaved changes on your screen may not be saved unless you take over again.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Take over',
            cancelButtonText: 'Go back to list',
            customClass: {
                confirmButton: 'btn btn-warning me-2',
                cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
            allowOutsideClick: false,
        }).then(function (result) {
            if (result.isConfirmed) {
                self.lockLostNotified = false;
                self.acquireLock(true);
                return;
            }

            if (self.config.indexUrl) {
                window.location.href = self.config.indexUrl;
            }
        });
    };

    PostEditor.prototype.showForeignLock = function (lock, showDialog) {
        this.isReadOnly = true;
        this.hasLock = false;
        this.currentLock = lock || null;
        this.setFormDisabled(true);
        this.updateLockStatus();

        if (this.lockBannerEl) {
            this.lockBannerEl.classList.remove('d-none');
        }

        if (this.lockBannerTextEl) {
            var name = (lock && lock.user_name) ? lock.user_name : 'Another user';
            this.lockBannerTextEl.textContent = name + ' is currently editing this post.';
        }

        if (showDialog) {
            this.showTakeOverDialog(false);
        }
    };

    PostEditor.prototype.hideForeignLock = function () {
        if (this.lockBannerEl) {
            this.lockBannerEl.classList.add('d-none');
        }
    };

    PostEditor.prototype.setFormDisabled = function (disabled) {
        var fields = this.form.querySelectorAll('input, textarea, select, button');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (field.type === 'hidden') {
                continue;
            }
            if (field.id === 'postEditorTakeOverBtn') {
                continue;
            }
            field.disabled = disabled;
        }

        var externalControls = document.querySelectorAll('[form="' + this.form.id + '"]');
        for (var j = 0; j < externalControls.length; j++) {
            externalControls[j].disabled = disabled;
        }

        if (window.tinymce) {
            window.tinymce.editors.forEach(function (editor) {
                if (disabled) {
                    editor.mode.set('readonly');
                } else {
                    editor.mode.set('design');
                }
            });
        }
    };

    PostEditor.prototype.updateLockStatus = function () {
        var lockStatusEl = document.getElementById('postEditorLockStatus');
        if (!lockStatusEl) {
            return;
        }

        if (this.isReadOnly && this.currentLock) {
            lockStatusEl.innerHTML = '<i class="ri-lock-line me-1"></i>Locked by ' + this.escapeHtml(this.currentLock.user_name || 'another user');
            return;
        }

        if (this.hasLock) {
            lockStatusEl.innerHTML = '<i class="ri-lock-unlock-line me-1 text-success"></i>You are editing';
            return;
        }

        lockStatusEl.textContent = '';
    };

    PostEditor.prototype.confirmTakeOver = function () {
        this.showTakeOverDialog(true);
    };

    PostEditor.prototype.showTakeOverDialog = function (fromButton) {
        var self = this;
        var name = (this.currentLock && this.currentLock.user_name) ? this.currentLock.user_name : 'another user';
        var message = name + ' is currently editing this post. Do you want to take over? Their changes may not be saved.';

        if (typeof Swal === 'undefined') {
            if (window.confirm(message)) {
                self.acquireLock(true);
            }
            return;
        }

        Swal.fire({
            title: 'Post locked',
            html: '<p class="mb-2"><i class="ri-lock-line me-1"></i><strong>' + self.escapeHtml(name) + '</strong> is currently editing this post.</p><p class="mb-0 text-muted small">Taking over will let you edit. The other user may lose their unsaved changes.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Take over',
            cancelButtonText: fromButton ? 'Cancel' : 'Go back',
            customClass: {
                confirmButton: 'btn btn-warning me-2',
                cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
            allowOutsideClick: !fromButton,
        }).then(function (result) {
            if (result.isConfirmed) {
                self.lockLostNotified = false;
                self.acquireLock(true);
                return;
            }

            if (!fromButton && self.config.indexUrl) {
                window.location.href = self.config.indexUrl;
            }
        });
    };

    PostEditor.prototype.setStatus = function (state, message) {
        if (!this.statusEl) {
            return;
        }

        var icon = '';
        if (state === 'saving') {
            icon = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>';
        } else if (state === 'saved') {
            icon = '<i class="ri-check-line me-1 text-success"></i>';
        } else if (state === 'error') {
            icon = '<i class="ri-error-warning-line me-1 text-danger"></i>';
        } else if (state === 'pending') {
            icon = '<i class="ri-time-line me-1 text-muted"></i>';
        }

        this.statusEl.innerHTML = icon + selfEscape(message);
    };

    PostEditor.prototype.escapeHtml = function (value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    function selfEscape(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function fetchJson(url, options) {
        return fetch(url, options).then(function (response) {
            return response.text().then(function (text) {
                var payload = {};

                if (text) {
                    try {
                        payload = JSON.parse(text);
                    } catch (error) {
                        payload = { message: 'Unexpected server response.' };
                    }
                }

                return {
                    ok: response.ok,
                    status: response.status,
                    payload: payload,
                };
            });
        });
    }

    window.initPostEditor = function (config) {
        var editor = new PostEditor(config);
        editor.init();
        window.__postEditor = editor;
        return editor;
    };

    /**
     * Call after programmatic field updates (media library, FAQ reorder, etc.)
     * so autosave + leave-guard dirty state stay in sync.
     */
    window.notifyPostEditorChanged = function () {
        document.dispatchEvent(new CustomEvent('post-editor:changed'));
    };
})();
