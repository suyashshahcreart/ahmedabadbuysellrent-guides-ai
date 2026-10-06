(function () {
    'use strict';

    function readPreviewConfig() {
        const fallback = {
            previewSample: {},
            defaultEmail: {},
            savedBodies: {},
        };

        const el = document.getElementById('contactFormPreviewConfig');
        if (!el) {
            return fallback;
        }

        try {
            const parsed = JSON.parse(el.textContent || '{}');
            return {
                previewSample: parsed.previewSample || {},
                defaultEmail: parsed.defaultEmail || {},
                savedBodies: parsed.savedBodies || {},
            };
        } catch (e) {
            return fallback;
        }
    }

    function initContactFormSettings() {
        const root = document.querySelector('[data-contact-form-settings]');
        if (!root) return;

        const previewConfig = readPreviewConfig();
        const sample = previewConfig.previewSample;
        const defaults = previewConfig.defaultEmail;
        const savedBodies = previewConfig.savedBodies;
        const previewUrl = root.getAttribute('data-preview-url') || '';

        const tagsRoot = document.getElementById('teamEmailTags');
        const tagsInput = document.getElementById('teamEmailInput');
        const tagsHidden = document.getElementById('teamEmailHidden');
        const previewSubject = document.getElementById('previewSubject');
        const previewBody = document.getElementById('previewBody');
        const previewNotice = document.getElementById('previewLayoutNotice');
        const previewModalEl = document.getElementById('contactEmailPreviewModal');
        const openPreviewBtn = document.getElementById('openLivePreviewModal');
        const refreshPreviewBtn = document.getElementById('refreshLivePreview');
        const form = document.getElementById('contactFormSettings');
        const templateTabs = root.querySelectorAll('[data-template-tab]');
        const customerEmailOnly = root.getAttribute('data-customer-email-only') === '1';

        let activeTemplate = customerEmailOnly ? 'customer' : 'team';
        let emails = [];

        function getPreviewModal() {
            if (!previewModalEl || !window.bootstrap || !window.bootstrap.Modal) {
                return null;
            }

            return window.bootstrap.Modal.getOrCreateInstance(previewModalEl, {
                backdrop: true,
                keyboard: true,
                focus: true,
            });
        }

        if (tagsRoot) {
            try {
                emails = JSON.parse(tagsRoot.getAttribute('data-emails') || '[]');
                if (!Array.isArray(emails)) emails = [];
            } catch (e) {
                emails = [];
            }
            emails = emails.map(function (e) { return String(e || '').trim().toLowerCase(); }).filter(Boolean);
        }

        function isValidEmail(value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        function syncHidden() {
            if (!tagsHidden) return;
            tagsHidden.innerHTML = '';
            emails.forEach(function (email) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'team_emails[]';
                input.value = email;
                tagsHidden.appendChild(input);
            });
        }

        function renderTags() {
            if (!tagsRoot || !tagsInput) return;
            tagsRoot.querySelectorAll('.contact-email-tag').forEach(function (el) { el.remove(); });
            emails.forEach(function (email, index) {
                const chip = document.createElement('span');
                chip.className = 'contact-email-tag';
                chip.innerHTML = '<span></span><button type="button" aria-label="Remove">&times;</button>';
                chip.querySelector('span').textContent = email;
                chip.querySelector('button').addEventListener('click', function () {
                    emails.splice(index, 1);
                    renderTags();
                });
                tagsRoot.insertBefore(chip, tagsInput);
            });
            syncHidden();
        }

        function addEmail(raw) {
            if (!tagsInput) return;
            const value = String(raw || '').trim().toLowerCase().replace(/,$/, '');
            if (!value) return;
            if (!isValidEmail(value)) {
                tagsInput.classList.add('is-invalid');
                return;
            }
            tagsInput.classList.remove('is-invalid');
            if (!emails.includes(value)) {
                emails.push(value);
                renderTags();
            }
            tagsInput.value = '';
        }

        if (tagsRoot && tagsInput) {
            tagsRoot.addEventListener('click', function () { tagsInput.focus(); });
            tagsInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addEmail(tagsInput.value);
                } else if (e.key === 'Backspace' && !tagsInput.value && emails.length) {
                    emails.pop();
                    renderTags();
                }
            });
            tagsInput.addEventListener('blur', function () { addEmail(tagsInput.value); });
            tagsInput.addEventListener('paste', function (e) {
                const text = (e.clipboardData || window.clipboardData).getData('text');
                if (!text || (text.indexOf(',') === -1 && text.indexOf(';') === -1 && text.indexOf(' ') === -1)) return;
                e.preventDefault();
                text.split(/[\s,;]+/).forEach(addEmail);
            });
        }

        function hasBrandedTeamShell(html) {
            const value = String(html || '');
            return /Team notification/i.test(value)
                && /Enquiry details/i.test(value)
                && (/\{\{\s*enquiry_details\s*\}\}/i.test(value) || /Action required:/i.test(value))
                && (/\{\{\s*form_title\s*\}\}/i.test(value) || /A new enquiry has been submitted/i.test(value));
        }

        function hasCompleteTeamEmailLayout(html) {
            const value = String(html || '');
            return hasBrandedTeamShell(value) && hasUnifiedEmailCardLayout(value);
        }

        function hasCompleteCustomerEmailLayout(html) {
            const value = String(html || '');
            return /PROP PIK/i.test(value) && hasUnifiedEmailCardLayout(value);
        }

        function hasUnifiedEmailCardLayout(html) {
            const value = String(html || '');
            const normalized = value
                .replace(/:\s+/g, ':')
                .replace(/\s*;/g, ';')
                .replace(/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/gi, 'rgb($1,$2,$3)')
                .toLowerCase();

            const hasCardWidth = normalized.indexOf('max-width:640px') !== -1;
            const hasBrandFooter = normalized.indexOf('background-color:#232872') !== -1
                || normalized.indexOf('background:#232872') !== -1
                || normalized.indexOf('background-color:rgb(35,40,114)') !== -1
                || normalized.indexOf('bgcolor="#232872"') !== -1
                || normalized.indexOf("bgcolor='#232872'") !== -1;
            const hasCardShell = normalized.indexOf('overflow:hidden') !== -1
                || normalized.indexOf('border-radius:16px') !== -1
                || normalized.indexOf('border-radius:12px') !== -1;

            if (!hasCardWidth || !hasBrandFooter || !hasCardShell) {
                return false;
            }

            return /max-width:\s*640px[\s\S]*background(?:-color)?:\s*(?:#232872|rgb\(\s*35\s*,\s*40\s*,\s*114\s*\))/i.test(value)
                || /max-width:\s*640px[\s\S]*bgcolor=["']?#232872/i.test(value);
        }

        function buildPreviewCss() {
            return '<style type="text/css" id="proppik-email-preview">'
                + 'body{margin:0;padding:0;background:#f0f2f5;}'
                + 'table[role="presentation"]{border-collapse:collapse;}'
                + 'table[style*="max-width:640px"]{width:100%!important;max-width:640px!important;}'
                + 'a[style*="ffffff"],a[style*="FFFFFF"],a[style*="#fff"],a[style*="#FFF"]{color:#ffffff !important;}'
                + 'a[style*="ffffff"] span,a[style*="FFFFFF"] span,a[style*="#fff"] span,a[style*="#FFF"] span{color:#ffffff !important;}'
                + 'td[bgcolor="#2b318d"] a,td[style*="2b318d"] a,td[bgcolor="#232872"] a,td[style*="232872"] a{color:#ffffff !important;}'
                + 'td[bgcolor="#2b318d"] a span,td[style*="2b318d"] a span,td[bgcolor="#232872"] a span,td[style*="232872"] a span{color:#ffffff !important;}'
                + '</style>';
        }

        function injectPreviewCss(html) {
            const content = String(html || '').trim();
            const previewCss = buildPreviewCss();

            if (/<head\b/i.test(content)) {
                if (/id="proppik-email-preview"/i.test(content)) {
                    return content;
                }

                return content.replace(/<head\b[^>]*>/i, '$&' + previewCss);
            }

            if (/<html[\s>]/i.test(content)) {
                return content.replace(/<html\b[^>]*>/i, '$&<head>' + previewCss + '</head>');
            }

            return previewCss + content;
        }

        function getEditorHtml(editorId) {
            if (window.tinymce) {
                tinymce.triggerSave();
                const ed = tinymce.get(editorId);
                if (ed) {
                    return ed.getContent({ format: 'html' }) || '';
                }
            }

            const el = document.getElementById(editorId);

            return el ? el.value : '';
        }

        function setEditorHtml(editorId, html) {
            if (window.tinymce) {
                const ed = tinymce.get(editorId);
                if (ed) {
                    ed.setContent(html || '');
                    return;
                }
            }
            const el = document.getElementById(editorId);
            if (el) el.value = html || '';
        }

        function activeFields() {
            return {
                type: activeTemplate,
                subject: document.getElementById(activeTemplate + '_email_subject'),
                bodyId: activeTemplate + '_email_body',
            };
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function looksLikeHtml(template) {
            return /<(?:html|body|table|div|p|br|h[1-6]|section|span)\b/i.test(String(template || ''));
        }

        function wrapPreviewDocument(html) {
            const content = String(html || '').trim();
            if (!content) {
                return content;
            }

            if (/<html[\s>]/i.test(content)) {
                return injectPreviewCss(content);
            }

            const fonts = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&amp;display=swap">';

            return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
                + fonts + buildPreviewCss() + '</head><body>' + content + '</body></html>';
        }

        function renderTemplate(template, vars, asHtml) {
            const rawKeys = ['fields_summary', 'enquiry_details'];
            let output = String(template || '');

            rawKeys.forEach(function (key) {
                if (!Object.prototype.hasOwnProperty.call(vars, key)) return;
                const re = new RegExp('\\{\\{\\s*' + key + '\\s*\\}\\}', 'g');
                output = output.replace(re, String(vars[key]));
            });

            return output.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function (_, key) {
                if (rawKeys.indexOf(key) !== -1) return '';
                if (!Object.prototype.hasOwnProperty.call(vars, key)) return '';
                const value = String(vars[key]);
                return asHtml ? escapeHtml(value) : value;
            });
        }

        function renderPreviewFrame(html) {
            if (!previewBody) return;

            previewBody.classList.remove('is-plain');
            previewBody.innerHTML = '';
            const frame = document.createElement('iframe');
            frame.title = 'Email template preview';
            frame.setAttribute('sandbox', '');
            frame.srcdoc = wrapPreviewDocument(html);
            frame.addEventListener('load', function () {
                try {
                    if (frame.contentWindow) {
                        frame.contentWindow.scrollTo(0, 0);
                    }
                } catch (e) {
                    // ignore sandbox access edge cases
                }
            });
            previewBody.appendChild(frame);
        }

        function updatePreview() {
            if (!previewSubject || !previewBody) return;
            const fields = activeFields();
            if (!fields.subject) return;

            const templateLabel = activeTemplate === 'team'
                ? 'Team notification'
                : (customerEmailOnly ? 'Customer OTP email' : 'Customer confirmation');
            const subjectValue = fields.subject.value;
            const bodyTemplate = getEditorHtml(fields.bodyId);

            if (previewUrl) {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        type: activeTemplate,
                        subject: subjectValue,
                        body: bodyTemplate,
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Preview request failed');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        previewSubject.innerHTML = '<span class="text-muted">Template:</span> <strong>' + templateLabel + '</strong>'
                            + ' &nbsp;·&nbsp; <span class="text-muted">Subject:</span> <strong>'
                            + (data.subject || '—') + '</strong>';
                        if (previewNotice) {
                            if (data.layout_complete === false) {
                                previewNotice.classList.remove('d-none');
                                previewNotice.textContent = 'This preview shows exactly what is in the Email body editor. The branded PROP PIK layout is incomplete — use Restore defaults or paste the full HTML template.';
                            } else {
                                previewNotice.classList.add('d-none');
                                previewNotice.textContent = '';
                            }
                        }
                        renderPreviewFrame(data.html || '—');
                    })
                    .catch(function () {
                        renderPreviewFallback(fields, bodyTemplate, templateLabel);
                    });

                return;
            }

            renderPreviewFallback(fields, bodyTemplate, templateLabel);
        }

        function renderPreviewFallback(fields, bodyTemplate, templateLabel) {
            const isHtml = looksLikeHtml(bodyTemplate);
            const layoutComplete = activeTemplate === 'team'
                ? hasCompleteTeamEmailLayout(bodyTemplate)
                : hasCompleteCustomerEmailLayout(bodyTemplate);

            previewSubject.innerHTML = '<span class="text-muted">Template:</span> <strong>' + templateLabel + '</strong>'
                + ' &nbsp;·&nbsp; <span class="text-muted">Subject:</span> <strong>'
                + (renderTemplate(fields.subject.value, sample, false) || '—') + '</strong>';

            if (previewNotice) {
                if (!layoutComplete) {
                    previewNotice.classList.remove('d-none');
                    previewNotice.textContent = 'This preview shows exactly what is in the Email body editor. The branded PROP PIK layout is incomplete — use Restore defaults or paste the full HTML template.';
                } else {
                    previewNotice.classList.add('d-none');
                    previewNotice.textContent = '';
                }
            }

            const rendered = renderTemplate(bodyTemplate, sample, isHtml) || '—';
            if (isHtml) {
                renderPreviewFrame(rendered);
            } else {
                previewBody.classList.add('is-plain');
                previewBody.textContent = rendered;
            }
        }

        function openPreview() {
            const modal = getPreviewModal();
            if (modal) {
                modal.show();
                updatePreview();
                return;
            }

            updatePreview();
            window.alert('Preview is loading. Please try again in a moment.');
        }

        function switchTemplate(type) {
            if (customerEmailOnly) {
                type = 'customer';
            }
            activeTemplate = (type === 'customer') ? 'customer' : 'team';
            templateTabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.getAttribute('data-template-tab') === activeTemplate);
            });
            document.querySelectorAll('.template-pane').forEach(function (pane) {
                pane.classList.toggle('is-hidden-template', pane.getAttribute('data-template') !== activeTemplate);
            });

            const emailTabInput = document.getElementById('email_tab');
            if (emailTabInput) {
                emailTabInput.value = activeTemplate;
            }

            try {
                const url = new URL(window.location.href);
                url.searchParams.set('email_tab', activeTemplate);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {
                // Ignore browsers without URL API.
            }
        }

        templateTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                switchTemplate(tab.getAttribute('data-template-tab'));
            });
        });

        if (openPreviewBtn) {
            openPreviewBtn.addEventListener('click', openPreview);
        }

        if (refreshPreviewBtn) {
            refreshPreviewBtn.addEventListener('click', updatePreview);
        }

        function confirmDialog(config) {
            if (typeof Swal === 'undefined') {
                return Promise.resolve(window.confirm(config.fallbackText || config.text || 'Are you sure?'));
            }

            return Swal.fire({
                title: config.title || 'Are you sure?',
                html: config.html || config.text || '',
                icon: config.icon || 'warning',
                showCancelButton: true,
                confirmButtonText: config.confirmText || 'Yes, confirm',
                cancelButtonText: config.cancelText || 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    confirmButton: (config.confirmClass || 'btn btn-danger') + ' me-2',
                    cancelButton: 'btn btn-outline-secondary',
                },
                buttonsStyling: false,
            }).then(function (result) {
                return !!result.isConfirmed;
            });
        }

        document.querySelectorAll('.contact-form-type-pill__edit').forEach(function (editBtn) {
            editBtn.addEventListener('click', function () {
                const form = document.getElementById('editFormTypeForm');
                const labelInput = document.getElementById('edit_form_label');
                const keyInput = document.getElementById('edit_form_key');
                const originalKeyInput = document.getElementById('edit_form_original_key');
                const lockedNote = document.getElementById('edit_form_key_locked_note');
                const keyHelp = document.getElementById('edit_form_key_help');
                if (!form || !labelInput || !keyInput) return;

                const key = editBtn.getAttribute('data-form-type-key') || '';
                const label = editBtn.getAttribute('data-form-type-label') || '';
                const locked = editBtn.getAttribute('data-form-type-locked') === '1';
                const updateUrl = editBtn.getAttribute('data-update-url') || '#';

                form.action = updateUrl;
                labelInput.value = label;
                keyInput.value = key;
                keyInput.readOnly = locked;
                keyInput.classList.toggle('bg-light', locked);

                if (originalKeyInput) {
                    originalKeyInput.value = key;
                }

                if (lockedNote && keyHelp) {
                    lockedNote.classList.toggle('d-none', !locked);
                    keyHelp.classList.toggle('d-none', locked);
                }

                delete keyInput.dataset.manual;
            });
        });

        document.querySelectorAll('.contact-form-type-pill__remove').forEach(function (removeForm) {
            removeForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const label = removeForm.getAttribute('data-form-type-label') || 'this form type';
                const key = removeForm.getAttribute('data-form-type-key') || '';

                confirmDialog({
                    title: 'Remove form type?',
                    html: '<p class="mb-2">Remove <strong>' + escapeHtml(label) + '</strong>'
                        + (key ? ' (<code>' + escapeHtml(key) + '</code>)' : '')
                        + ' from the list?</p>'
                        + '<p class="mb-0 text-muted small">Saved email templates for this key stay in the database but will no longer be used for new submissions.</p>',
                    confirmText: 'Yes, remove',
                    cancelText: 'Keep form type',
                    icon: 'warning',
                    fallbackText: 'Remove "' + label + '"? Email settings stay saved but will no longer be used.',
                }).then(function (confirmed) {
                    if (confirmed) {
                        HTMLFormElement.prototype.submit.call(removeForm);
                    }
                });
            });
        });

        const restoreBtn = document.getElementById('restoreDefaultTemplates');
        if (restoreBtn) {
            restoreBtn.addEventListener('click', function () {
                confirmDialog({
                    title: 'Restore default templates?',
                    html: customerEmailOnly
                        ? '<p class="mb-0">Replace the <strong>customer OTP</strong> email template with the PROP PIK branded default?</p>'
                        : '<p class="mb-0">Replace both <strong>team</strong> and <strong>customer</strong> email templates with the PROP PIK branded defaults for this form type?</p>',
                    confirmText: 'Yes, restore defaults',
                    cancelText: 'Cancel',
                    icon: 'question',
                    confirmClass: 'btn btn-primary',
                    fallbackText: customerEmailOnly
                        ? 'Replace the customer OTP email template with the PROP PIK branded default?'
                        : 'Replace both email templates with the PROP PIK branded defaults for this form type?',
                }).then(function (confirmed) {
                    if (!confirmed) return;
                    const teamSubject = document.getElementById('team_email_subject');
                    const customerSubject = document.getElementById('customer_email_subject');
                    if (teamSubject) {
                        teamSubject.value = defaults.team_email_subject || defaults.team_subject || '';
                    }
                    if (customerSubject) {
                        customerSubject.value = defaults.customer_email_subject || defaults.customer_subject || '';
                    }
                    setEditorHtml('team_email_body', defaults.team_email_body || defaults.team_body || '');
                    setEditorHtml('customer_email_body', defaults.customer_email_body || defaults.customer_body || '');
                });
            });
        }

        const placeholderRoot = document.getElementById('placeholderChips');
        if (placeholderRoot) {
            placeholderRoot.addEventListener('click', function (e) {
                const chip = e.target.closest('[data-placeholder]');
                if (!chip) return;
                const fields = activeFields();
                const token = chip.getAttribute('data-placeholder');

                if (window.tinymce) {
                    const ed = tinymce.get(fields.bodyId);
                    if (ed) {
                        ed.focus();
                        ed.insertContent(token);
                        return;
                    }
                }

                const textarea = document.getElementById(fields.bodyId);
                if (!textarea) return;
                const start = textarea.selectionStart || 0;
                const end = textarea.selectionEnd || 0;
                const value = textarea.value;
                textarea.value = value.slice(0, start) + token + value.slice(end);
                textarea.focus();
                const pos = start + token.length;
                textarea.setSelectionRange(pos, pos);
            });
        }

        if (form) {
            form.addEventListener('submit', function () {
                if (window.tinymce) tinymce.triggerSave();
            });
        }

        renderTags();

        const initialTab = customerEmailOnly || (root.getAttribute('data-initial-email-tab') || 'team') === 'customer'
            ? 'customer'
            : 'team';
        switchTemplate(initialTab);

        function resetEditorScroll(editorId) {
            if (!window.tinymce) return;
            const ed = tinymce.get(editorId);
            if (!ed || !ed.getWin) return;
            try {
                ed.getWin().scrollTo(0, 0);
            } catch (e) {
                // ignore
            }
        }

        window.setTimeout(function () {
            resetEditorScroll('team_email_body');
            resetEditorScroll('customer_email_body');
        }, 1200);
    }

    function whenBootstrapReady(callback) {
        if (window.bootstrap && window.bootstrap.Modal) {
            callback();
            return;
        }

        let tries = 0;
        const timer = setInterval(function () {
            tries += 1;
            if ((window.bootstrap && window.bootstrap.Modal) || tries > 60) {
                clearInterval(timer);
                callback();
            }
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            whenBootstrapReady(initContactFormSettings);
        });
    } else {
        whenBootstrapReady(initContactFormSettings);
    }
})();
