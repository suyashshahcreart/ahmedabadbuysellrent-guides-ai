/**
 * Warn before leaving a settings form with unsaved changes.
 * SweetAlert for in-app navigation; native beforeunload for tab close.
 */
(function (window) {
    'use strict';

    function FormLeaveGuard(options) {
        this.form = typeof options.form === 'string'
            ? document.getElementById(options.form)
            : options.form;

        if (!this.form) {
            return;
        }

        this.title = options.title || 'Unsaved changes';
        this.saveButtonText = options.saveButtonText || 'Save & leave';
        this.leaveButtonText = options.leaveButtonText || 'Leave without saving';
        this.stayButtonText = options.stayButtonText || 'Stay on page';
        this.fieldLabels = options.fieldLabels || {};
        this.allowLeave = false;
        this.leaveConfirmOpen = false;
        this.pendingRedirect = null;
        this.pendingAction = null;
        this.suppressSubmitUntil = 0;
        this.initialSnapshot = this.buildSnapshot();

        this.ensureRedirectInput();
        this.bindEvents();
    }

    FormLeaveGuard.prototype.ensureRedirectInput = function () {
        if (!this.form.querySelector('[name="redirect_after_save"]')) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'redirect_after_save';
            input.value = '';
            this.form.appendChild(input);
        }
    };

    FormLeaveGuard.prototype.buildSnapshot = function () {
        var entries = [];
        var fd = new FormData(this.form);

        fd.forEach(function (value, key) {
            if (key === '_token' || key === 'redirect_after_save') {
                return;
            }
            entries.push([key, String(value)]);
        });

        entries.sort(function (a, b) {
            if (a[0] !== b[0]) {
                return a[0] < b[0] ? -1 : 1;
            }
            return a[1] < b[1] ? -1 : (a[1] > b[1] ? 1 : 0);
        });

        return JSON.stringify(entries);
    };

    FormLeaveGuard.prototype.isDirty = function () {
        return this.buildSnapshot() !== this.initialSnapshot;
    };

    FormLeaveGuard.prototype.labelForField = function (key) {
        if (this.fieldLabels[key]) {
            return this.fieldLabels[key];
        }

        if (key.indexOf('static_pages[') === 0) {
            return 'Static pages';
        }

        return key.replace(/_/g, ' ').replace(/\b\w/g, function (c) {
            return c.toUpperCase();
        });
    };

    FormLeaveGuard.prototype.describeChanges = function () {
        var initial = JSON.parse(this.initialSnapshot || '[]');
        var current = JSON.parse(this.buildSnapshot() || '[]');
        var initialMap = {};
        var currentMap = {};

        initial.forEach(function (pair) {
            initialMap[pair[0]] = pair[1];
        });
        current.forEach(function (pair) {
            currentMap[pair[0]] = pair[1];
        });

        var keys = {};
        Object.keys(initialMap).forEach(function (k) { keys[k] = true; });
        Object.keys(currentMap).forEach(function (k) { keys[k] = true; });

        var lines = [];
        var staticChanged = false;

        Object.keys(keys).sort().forEach(function (key) {
            var before = initialMap[key];
            var after = currentMap[key];

            if (before === after) {
                return;
            }

            if (key.indexOf('static_pages[') === 0) {
                staticChanged = true;
                return;
            }

            var label = this.labelForField(key);
            if (before === undefined) {
                lines.push('<li><strong>' + label + '</strong> set to <code>' + this.escapeHtml(after) + '</code></li>');
            } else if (after === undefined) {
                lines.push('<li><strong>' + label + '</strong> turned off</li>');
            } else {
                lines.push('<li><strong>' + label + '</strong> changed</li>');
            }
        }, this);

        if (staticChanged) {
            lines.push('<li><strong>Static pages</strong> list updated</li>');
        }

        if (!lines.length) {
            return '<p class="mb-2">You have unsaved sitemap settings on this page.</p>';
        }

        return '<p class="mb-2">You changed:</p><ul class="text-start small mb-0 ps-3">' + lines.join('') + '</ul>';
    };

    FormLeaveGuard.prototype.escapeHtml = function (value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    FormLeaveGuard.prototype.buildDialogHtml = function (extraNote) {
        return this.describeChanges()
            + '<p class="mb-0 text-muted small mt-2">' + (extraNote || 'Save your settings before leaving, or leave without saving.') + '</p>';
    };

    FormLeaveGuard.prototype.markSaved = function () {
        this.initialSnapshot = this.buildSnapshot();
        this.allowLeave = true;
    };

    FormLeaveGuard.prototype.confirmLeave = function (href, options) {
        var self = this;
        options = options || {};

        if (this.leaveConfirmOpen || !this.isDirty()) {
            if (href) {
                window.location.href = href;
            } else if (options.reload) {
                window.location.reload();
            } else if (options.submitForm) {
                options.submitForm.submit();
            }
            return;
        }

        this.leaveConfirmOpen = true;
        this.pendingRedirect = href || null;
        this.pendingAction = options.submitForm || null;

        var leaveNow = function () {
            var targetHref = self.pendingRedirect;
            var targetForm = self.pendingAction;
            var shouldReload = !!options.reload;

            self.leaveConfirmOpen = false;
            self.pendingRedirect = null;
            self.pendingAction = null;

            // Block SweetAlert click-through from hitting "Save Settings" underneath.
            self.suppressSubmitUntil = Date.now() + 600;

            var redirectInput = self.form.querySelector('[name="redirect_after_save"]');
            if (redirectInput) {
                redirectInput.value = '';
            }

            setTimeout(function () {
                self.allowLeave = true;

                if (shouldReload) {
                    window.location.reload();
                    return;
                }
                if (targetForm) {
                    targetForm.submit();
                    return;
                }
                if (targetHref) {
                    window.location.href = targetHref;
                }
            }, 120);
        };

        var stay = function () {
            self.leaveConfirmOpen = false;
            self.pendingRedirect = null;
            self.pendingAction = null;
        };

        var saveAndLeave = function () {
            var redirectInput = self.form.querySelector('[name="redirect_after_save"]');
            if (redirectInput) {
                if (options.reload) {
                    redirectInput.value = window.location.href;
                } else if (self.pendingRedirect) {
                    redirectInput.value = self.pendingRedirect;
                } else {
                    redirectInput.value = '';
                }
            }

            self.suppressSubmitUntil = 0;
            self.allowLeave = true;
            self.leaveConfirmOpen = false;
            self.pendingRedirect = null;
            self.pendingAction = null;

            setTimeout(function () {
                self.form.requestSubmit();
            }, 0);
        };

        var title = options.reload ? 'Reload this page?' : this.title;
        var body = this.buildDialogHtml(
            options.reload
                ? 'Save your settings first, reload without saving, or stay on the page.'
                : 'Save your settings before leaving, or leave without saving.'
        );

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
            confirmButtonText: options.reload ? 'Save & reload' : this.saveButtonText,
            denyButtonText: options.reload ? 'Reload without saving' : this.leaveButtonText,
            cancelButtonText: this.stayButtonText,
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
                saveAndLeave();
                return;
            }
            if (result.isDenied) {
                leaveNow();
                return;
            }
            stay();
        });
    };

    FormLeaveGuard.prototype.bindEvents = function () {
        var self = this;

        this.form.addEventListener('submit', function (event) {
            if (self.suppressSubmitUntil && Date.now() < self.suppressSubmitUntil) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }
            self.allowLeave = true;
        });

        window.addEventListener('beforeunload', function (event) {
            if (self.allowLeave || !self.isDirty()) {
                return;
            }
            event.preventDefault();
            event.returnValue = 'You have unsaved sitemap settings.';
        });

        document.addEventListener('keydown', function (event) {
            if (self.allowLeave || self.leaveConfirmOpen || !self.isDirty()) {
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
            if (self.allowLeave || self.leaveConfirmOpen || !self.isDirty()) {
                return;
            }

            var link = event.target.closest('a[href]');
            if (link) {
                var href = link.getAttribute('href');
                if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
                    return;
                }
                if (link.target === '_blank' || link.hasAttribute('download')
                    || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
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
                return;
            }

            var submitBtn = event.target.closest('button[type="submit"], input[type="submit"]');
            if (!submitBtn) {
                return;
            }
            var otherForm = submitBtn.form;
            if (!otherForm || otherForm === self.form) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            self.confirmLeave(null, { submitForm: otherForm });
        }, true);
    };

    window.FormLeaveGuard = {
        init: function (options) {
            return new FormLeaveGuard(options);
        },
    };
})(window);
