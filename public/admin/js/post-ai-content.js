/**
 * Albergoes AI Content Generation
 *
 * Expects window.aiConfig = {
 *   generateTitleUrl,
 *   generateContentUrl,
 *   generateSeoUrl,   // optional — only on edit pages
 *   csrfToken
 * }
 *
 * HTML elements expected on page:
 *   #loadingOverlay, #loadingText
 *   #generateTitleBtn
 *   #expandArticleBtn   (shows LLM consent modal first)
 *   #generateSeoBtn     (edit page only)
 *   #llmConsentModal, #llmConsentConfirmBtn
 *
 * TinyMCE editor id: 'blog-content-editor'
 * SEO field ids: #seoTitle, #metaDescription, #focusKeyphrase
 */
(function () {
    'use strict';

    function getPostTitleInput() {
        return document.getElementById('gallery-post-title') || document.getElementById('title');
    }

    // ── Typing animation phrases ──────────────────────────────────────────────
    var phrases = [
        'Albergoes AI is thinking... 🧠',
        'Consulting the AI oracle...',
        'Spinning words into gold...',
        'Crafting the perfect content...',
        'Optimizing for SEO...',
        'Brewing a cup of creativity ☕...',
        'Interviewing virtual experts...',
        'Tuning into the content matrix...',
        'Generating brilliant insights...',
        'Fact-checking at hyperspeed...',
        'Adding a dash of brilliance...',
        'Polishing every sentence...',
    ];

    var phraseIndex  = 0;
    var letterIndex  = 0;
    var isTyping     = true;
    var typingTimer  = null;

    function typeLoop() {
        var textEl = document.getElementById('loadingText');
        if (! textEl) return;

        if (isTyping) {
            if (letterIndex <= phrases[phraseIndex].length) {
                textEl.textContent = phrases[phraseIndex].substring(0, letterIndex++);
                typingTimer = setTimeout(typeLoop, 50);
            } else {
                isTyping = false;
                typingTimer = setTimeout(typeLoop, 1000);
            }
        } else {
            if (letterIndex >= 0) {
                textEl.textContent = phrases[phraseIndex].substring(0, letterIndex--);
                typingTimer = setTimeout(typeLoop, 30);
            } else {
                isTyping = true;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                typingTimer = setTimeout(typeLoop, 200);
            }
        }
    }

    function startTyping() {
        phraseIndex = 0;
        letterIndex = 0;
        isTyping    = true;
        typeLoop();
    }

    function stopTyping() {
        if (typingTimer) clearTimeout(typingTimer);
        var textEl = document.getElementById('loadingText');
        if (textEl) textEl.textContent = '';
    }

    // ── Overlay helpers ───────────────────────────────────────────────────────
    function showOverlay() {
        var overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('d-none');
            document.body.style.overflow = 'hidden';
        }
        startTyping();
    }

    function hideOverlay() {
        var overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('d-none');
            document.body.style.overflow = '';
        }
        stopTyping();
    }

    // ── Toast helper ──────────────────────────────────────────────────────────
    function toast(type, message) {
        if (window.Swal) {
            Swal.fire({
                toast:             true,
                position:          'top-end',
                showConfirmButton: false,
                timer:             4000,
                timerProgressBar:  true,
                icon:              type,
                title:             message,
            });
        } else {
            alert(message);
        }
    }

    // ── Success flash ─────────────────────────────────────────────────────────
    function flashSuccess(message) {
        var cardBody = document.querySelector('.card-body');
        if (! cardBody) return;
        var div = document.createElement('div');
        div.className = 'alert alert-success alert-dismissible mt-2 py-2';
        div.innerHTML = '<i class="ri-check-line me-2"></i>' + message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        cardBody.prepend(div);
        setTimeout(function () { div.remove(); }, 4000);
    }

    // ── POST helper ───────────────────────────────────────────────────────────
    function postAI(url, body) {
        return fetch(url, {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  (window.aiConfig || {}).csrfToken ||
                                 (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
            },
            body: JSON.stringify(body),
        });
    }

    // ── Get TinyMCE content safely ────────────────────────────────────────────
    function getTinyMceContent() {
        if (window.tinymce && tinymce.get('gallery-intro-editor')) {
            return tinymce.get('gallery-intro-editor').getContent();
        }
        if (window.tinymce && tinymce.get('blog-content-editor')) {
            return tinymce.get('blog-content-editor').getContent();
        }
        return '';
    }

    function setTinyMceContent(html) {
        if (window.tinymce && tinymce.get('gallery-intro-editor')) {
            var galleryEditor = tinymce.get('gallery-intro-editor');
            galleryEditor.setContent(html);
            galleryEditor.fire('change');
            return;
        }
        if (window.tinymce && tinymce.get('blog-content-editor')) {
            var editor = tinymce.get('blog-content-editor');
            editor.setContent(html);
            editor.fire('change');
        }
    }

    // ── Generate Title ────────────────────────────────────────────────────────
    function bindGenerateTitle() {
        var btn = document.getElementById('generateTitleBtn');
        if (! btn) return;

        btn.addEventListener('click', async function () {
            var cfg   = window.aiConfig || {};
            var url   = cfg.generateTitleUrl;
            var title = (getPostTitleInput() || {}).value;

            if (! title || ! title.trim()) {
                toast('warning', 'Please enter a title first.');
                return;
            }
            if (! url) { toast('error', 'AI is not configured on this page.'); return; }

            showOverlay();
            try {
                var res  = await postAI(url, { title: title.trim() });
                var data = await res.json();

                if (! res.ok || ! data.success) {
                    throw new Error(data.message || 'Failed to generate title.');
                }

                var titleInput = getPostTitleInput();
                if (titleInput) {
                    titleInput.value = data.title;
                    titleInput.dispatchEvent(new Event('input', { bubbles: true }));
                    titleInput.classList.remove('is-invalid');
                }

                flashSuccess('Title optimized successfully!');
            } catch (err) {
                toast('error', err.message || 'An unexpected error occurred.');
            } finally {
                hideOverlay();
            }
        });
    }

    // ── Generate Content ──────────────────────────────────────────────────────
    function bindGenerateContent() {
        var expandBtn   = document.getElementById('expandArticleBtn');
        var confirmBtn  = document.getElementById('llmConsentConfirmBtn');

        if (! expandBtn) return;

        var cfg = window.aiConfig || {};
        var url = cfg.generateContentUrl;

        async function doGenerate() {
            var title = (getPostTitleInput() || {}).value;

            if (! title || ! title.trim()) {
                toast('warning', 'Please enter a title first.');
                return;
            }
            if (! url) { toast('error', 'AI is not configured on this page.'); return; }

            showOverlay();
            try {
                var res  = await postAI(url, {
                    title:            title.trim(),
                    existing_content: getTinyMceContent(),
                });
                var data = await res.json();

                if (! res.ok || ! data.success) {
                    throw new Error(data.message || 'Failed to generate content.');
                }

                setTinyMceContent(data.content);
                flashSuccess('Content generated successfully!');
            } catch (err) {
                toast('error', err.message || 'An unexpected error occurred.');
            } finally {
                hideOverlay();
            }
        }

        // expandArticleBtn triggers consent modal first
        expandBtn.addEventListener('click', function () {
            var modal = document.getElementById('llmConsentModal');
            if (modal && window.bootstrap) {
                new bootstrap.Modal(modal).show();
            } else {
                doGenerate();
            }
        });

        // Confirm button inside consent modal
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                var modal = document.getElementById('llmConsentModal');
                if (modal && window.bootstrap) {
                    var bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                }
                doGenerate();
            });
        }
    }

    // ── Generate SEO ──────────────────────────────────────────────────────────
    function bindGenerateSeo() {
        var btn = document.getElementById('generateSeoBtn');
        if (! btn) return;

        btn.addEventListener('click', async function () {
            var cfg     = window.aiConfig || {};
            var url     = cfg.generateSeoUrl;
            var title   = (getPostTitleInput() || {}).value;
            var content = getTinyMceContent();

            if (! title || ! title.trim()) {
                toast('warning', 'Please enter a title first.');
                return;
            }
            if (! content || ! content.trim()) {
                toast('warning', 'Please add some content first.');
                return;
            }
            if (! url) { toast('error', 'AI is not configured on this page.'); return; }

            showOverlay();
            try {
                var res  = await postAI(url, { title: title.trim(), content: content });
                var data = await res.json();

                if (! res.ok || ! data.success) {
                    throw new Error(data.message || 'Failed to generate SEO metadata.');
                }

                var seo = data.seo_metadata;
                var titleEl = document.getElementById('seoTitleInput');
                var descEl = document.getElementById('seoDescInput');
                var keywordsEl = document.querySelector('[name="seo_keywords"]');

                if (titleEl && seo.seo_title) {
                    titleEl.value = seo.seo_title;
                    titleEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (descEl && seo.meta_description) {
                    descEl.value = seo.meta_description;
                    descEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (keywordsEl && seo.focus_keyphrase) {
                    keywordsEl.value = seo.focus_keyphrase;
                    keywordsEl.dispatchEvent(new Event('input', { bubbles: true }));
                }

                flashSuccess('SEO metadata generated successfully!');
            } catch (err) {
                toast('error', err.message || 'An unexpected error occurred.');
            } finally {
                hideOverlay();
            }
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        bindGenerateTitle();
        bindGenerateContent();
        bindGenerateSeo();
    });
}());
