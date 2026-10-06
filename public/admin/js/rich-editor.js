/**
 * rich-editor.js — Shared TinyMCE initializer for the Albergoes admin panel.
 *
 * Usage:
 *   initRichEditor({ editorId: 'my-editor', height: 400, mediaModalId: 'myMediaModal' });
 *
 * The function returns the TinyMCE init Promise so callers can chain .then() if needed.
 *
 * Options:
 *   editorId         {string}  — ID of the <textarea> TinyMCE will attach to (required)
 *   height           {number}  — Editor height in px (default: 400)
 *   placeholder      {string}  — Placeholder text (default: '')
 *   showWordCount    {bool}    — Show word count below editor (default: false)
 *   wordCountElId    {string}  — ID of the <span> element for word count (auto-derived if omitted)
 *   mediaModalId     {string}  — Modal ID for the image media-library toolbar button (default: editorContentMediaModal; pass false to disable)
 *   enableIconPicker {bool}    — Show Insert Icon toolbar button (default: true)
 *   minWords         {number}  — Minimum word count threshold for warning colour (default: 0 = disabled)
 *   editorType       {string}  — Toolbar preset: full, medium, or small (default: full)
 *                                Aliases: editorSize, toolbarMode, variant
 */

function _getRichEditorPreviewBaseUrl() {
    if (window.appConfig && window.appConfig.baseUrl) {
        return String(window.appConfig.baseUrl).replace(/\/$/, '') + '/';
    }

    return window.location.origin + '/';
}

function _buildRichEditorPreviewHtml(editor) {
    var encode = function (value) {
        return editor.dom.encode(value == null ? '' : String(value));
    };
    var baseUrl = _getRichEditorPreviewBaseUrl();
    var contentStyle = editor.options.get('content_style') || '';
    var bodyClass = editor.options.get('body_class') || '';
    var bodyId = editor.options.get('body_id') || '';
    var bodyDir = editor.getBody().dir;
    var dirAttr = bodyDir ? ' dir="' + encode(bodyDir) + '"' : '';
    var contentCssCors = editor.options.get('content_css_cors');
    var corsAttr = contentCssCors ? ' crossorigin="anonymous"' : '';
    var headLinks = '';

    (editor.contentCSS || []).forEach(function (cssPath) {
        var href = editor.documentBaseURI.toAbsolute(cssPath);
        headLinks += '<link type="text/css" rel="stylesheet" href="' + encode(href) + '"' + corsAttr + '>';
    });

    return '<!DOCTYPE html><html><head>' +
        '<meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width, initial-scale=1">' +
        '<base href="' + encode(baseUrl) + '">' +
        headLinks +
        (contentStyle ? '<style type="text/css">' + contentStyle + '</style>' : '') +
        '</head><body id="' + encode(bodyId) + '" class="mce-content-body ' + encode(bodyClass) + '"' + dirAttr + '>' +
        editor.getContent() +
        '<script>(function(){document.addEventListener("click",function(e){var t=e.target;while(t){if(t.classList&&t.classList.contains("proppik-embed-overlay")){t.style.display="none";return;}t=t.parentNode;}});})();<\/script>' +
        '</body></html>';
}

function _ensureRichEditorPreviewStyles() {
    if (document.getElementById('rich-editor-preview-styles')) {
        return;
    }

    var style = document.createElement('style');
    style.id = 'rich-editor-preview-styles';
    style.textContent = [
        '.tox-dialog-wrap.rich-editor-preview-wrap {',
        '  align-items: stretch !important;',
        '  justify-content: stretch !important;',
        '  padding: 12px !important;',
        '  z-index: 10050 !important;',
        '}',
        '.tox-dialog-wrap.rich-editor-preview-wrap .tox-dialog {',
        '  max-width: none !important;',
        '  width: calc(100vw - 24px) !important;',
        '  height: calc(100vh - 24px) !important;',
        '  max-height: calc(100vh - 24px) !important;',
        '  margin: 0 !important;',
        '  display: flex !important;',
        '  flex-direction: column !important;',
        '}',
        '.tox-dialog-wrap.rich-editor-preview-wrap .tox-dialog__body {',
        '  flex: 1 1 auto !important;',
        '  min-height: 0 !important;',
        '  overflow: hidden !important;',
        '}',
        '.tox-dialog-wrap.rich-editor-preview-wrap .tox-dialog__body-content {',
        '  height: 100% !important;',
        '  max-height: none !important;',
        '  overflow: hidden !important;',
        '  padding: 0 !important;',
        '}',
        '.tox-dialog-wrap.rich-editor-preview-wrap .tox-form,',
        '.tox-dialog-wrap.rich-editor-preview-wrap .tox-form__group,',
        '.tox-dialog-wrap.rich-editor-preview-wrap .tox-navobj {',
        '  height: 100% !important;',
        '  min-height: 0 !important;',
        '}',
        '.tox-dialog-wrap.rich-editor-preview-wrap .tox-navobj iframe,',
        '.tox-dialog-wrap.rich-editor-preview-wrap iframe.tox-dialog__iframe--opaque {',
        '  width: 100% !important;',
        '  min-height: calc(100vh - 150px) !important;',
        '  height: calc(100vh - 150px) !important;',
        '  border: 0 !important;',
        '}',
    ].join('\n');
    document.head.appendChild(style);
}

function _applyRichEditorPreviewFullscreen() {
    _ensureRichEditorPreviewStyles();

    var apply = function () {
        var wraps = document.querySelectorAll('.tox-dialog-wrap');
        var wrap = wraps[wraps.length - 1];
        if (!wrap) {
            return;
        }

        var titleEl = wrap.querySelector('.tox-dialog__title');
        if (!titleEl || String(titleEl.textContent || '').trim() !== 'Preview') {
            return;
        }

        wrap.classList.add('rich-editor-preview-wrap');
    };

    requestAnimationFrame(apply);
    window.setTimeout(apply, 0);
    window.setTimeout(apply, 50);
}

function _openRichEditorPreview(editor) {
    if (!editor || editor.removed) {
        return;
    }

    var previewHtml = _buildRichEditorPreviewHtml(editor);
    var dialogApi = editor.windowManager.open({
        title: 'Preview',
        size: 'large',
        body: {
            type: 'panel',
            items: [{
                name: 'preview',
                type: 'iframe',
                sandboxed: false,
                transparent: false,
            }],
        },
        buttons: [{
            type: 'cancel',
            name: 'close',
            text: 'Close',
            primary: true,
        }],
        initialData: {
            preview: previewHtml,
        },
    });

    _applyRichEditorPreviewFullscreen();
    dialogApi.focus('close');
}

function _stripSandboxFromEmbedIframes(editor) {
    if (!editor || !editor.getBody) {
        return;
    }

    editor.getBody().querySelectorAll('iframe[sandbox]').forEach(function (iframe) {
        iframe.removeAttribute('sandbox');
    });
}

/**
 * PROP PIK tour embeds are a wrapper div + overlay + iframe. TinyMCE's media dialog
 * only treats a bare iframe as editable media, so select/remove/edit breaks.
 * Mark the outer tour wrapper as a single non-editable block in the editor.
 */
function _isProppikTourEmbedRoot(el) {
    if (!el || el.nodeType !== 1 || el.nodeName !== 'DIV') {
        return false;
    }
    if (el.getAttribute('data-mce-proppik-embed') === '1' || el.classList.contains('mce-proppik-embed')) {
        return true;
    }

    var hasOverlay = false;
    var iframe = null;
    var i;
    var child;
    for (i = 0; i < el.children.length; i++) {
        child = el.children[i];
        if (child.classList && child.classList.contains('proppik-embed-overlay')) {
            hasOverlay = true;
        }
        if (child.nodeName === 'IFRAME' && child.getAttribute('src')) {
            iframe = child;
        }
        // TinyMCE media plugin may wrap the iframe in a preview placeholder.
        if (child.nodeName === 'SPAN' && child.getAttribute('data-mce-object') === 'iframe') {
            iframe = child.querySelector('iframe[src]') || iframe;
        }
    }

    if (hasOverlay && iframe) {
        return true;
    }
    if (!iframe) {
        return false;
    }
    var src = String(iframe.getAttribute('src') || '').toLowerCase();
    if (src.indexOf('proppik.') !== -1 || src.indexOf('proppik.in') !== -1 || src.indexOf('/portfolio/') !== -1) {
        var style = String(el.getAttribute('style') || '');
        if (style.indexOf('position') !== -1 && (style.indexOf('min-height') !== -1 || style.indexOf('80vh') !== -1)) {
            return true;
        }
    }
    return false;
}

function _unwrapMediaPreviewObjects(root) {
    if (!root || !root.querySelectorAll) {
        return;
    }
    Array.prototype.slice.call(root.querySelectorAll('span.mce-preview-object[data-mce-object="iframe"]')).forEach(function (span) {
        var iframe = span.querySelector('iframe');
        if (!iframe || !span.parentNode) {
            return;
        }
        // Only unwrap when this is part of a tour-style wrapper (keep normal video placeholders).
        var parent = span.parentNode;
        if (!_isProppikTourEmbedRoot(parent) && !(parent.classList && parent.classList.contains('mce-proppik-embed'))) {
            var hasOverlay = parent.querySelector && parent.querySelector('.proppik-embed-overlay');
            var src = String(iframe.getAttribute('src') || '').toLowerCase();
            var isTourSrc = src.indexOf('proppik.') !== -1 || src.indexOf('proppik.in') !== -1 || src.indexOf('/portfolio/') !== -1;
            if (!hasOverlay && !isTourSrc) {
                return;
            }
        }
        span.parentNode.insertBefore(iframe, span);
        span.parentNode.removeChild(span);
    });
}

function _scrubIframeEditorStyles(iframe) {
    if (!iframe || iframe.nodeType !== 1) {
        return;
    }
    iframe.removeAttribute('sandbox');
    // Never persist editor-only interaction locks into saved / edit HTML.
    ['style', 'data-mce-style'].forEach(function (attr) {
        var value = String(iframe.getAttribute(attr) || '');
        if (!value) {
            return;
        }
        var cleaned = value
            .replace(/(?:^|;)\s*pointer-events\s*:\s*[^;]+/gi, '')
            .replace(/(?:^|;)\s*visibility\s*:\s*visible\s*/gi, '')
            .replace(/(?:^|;)\s*opacity\s*:\s*1\s*/gi, '')
            .replace(/;\s*;/g, ';')
            .replace(/^\s*;\s*|\s*;\s*$/g, '')
            .trim();
        if (cleaned) {
            iframe.setAttribute(attr, cleaned);
        } else {
            iframe.removeAttribute(attr);
        }
    });
    if (iframe.style) {
        iframe.style.pointerEvents = '';
    }
}

function _markProppikTourEmbed(el) {
    if (!el || el.nodeType !== 1) {
        return;
    }
    _unwrapMediaPreviewObjects(el);
    el.setAttribute('contenteditable', 'false');
    el.setAttribute('data-mce-proppik-embed', '1');
    el.classList.add('mce-proppik-embed');
    el.setAttribute('data-mce-resize', 'false');
    el.removeAttribute('title');
    var style = String(el.getAttribute('style') || '');
    if (style.indexOf('position') === -1) {
        el.style.position = 'relative';
    }
    Array.prototype.slice.call(el.querySelectorAll('iframe')).forEach(function (iframe) {
        _scrubIframeEditorStyles(iframe);
    });
    _ensureProppikEditButton(el);
}

function _ensureProppikEditButton(el) {
    if (!el || el.querySelector('.mce-proppik-edit-btn')) {
        return;
    }
    var btn = el.ownerDocument.createElement('button');
    btn.type = 'button';
    btn.className = 'mce-proppik-edit-btn';
    btn.setAttribute('contenteditable', 'false');
    btn.setAttribute('data-mce-proppik-chrome', '1');
    btn.textContent = 'Edit tour';
    btn.title = 'Edit or remove this PROP PIK tour';
    el.insertBefore(btn, el.firstChild);
}

function _injectProppikEditorOnlyStyles(editor) {
    if (!editor || !editor.getDoc) {
        return;
    }
    var doc = editor.getDoc();
    if (!doc || !doc.head) {
        return;
    }
    if (doc.getElementById('mce-proppik-editor-only')) {
        return;
    }
    var style = doc.createElement('style');
    style.id = 'mce-proppik-editor-only';
    // Keep the tour fully clickable. Only the small Edit button is editor chrome.
    style.textContent = [
        '.mce-proppik-embed { outline: 1px dashed #94a3b8; outline-offset: 2px; }',
        '.mce-proppik-embed .mce-proppik-edit-btn {',
        '  position: absolute; top: 8px; left: 8px; z-index: 30;',
        '  pointer-events: auto !important; cursor: pointer;',
        '  background: rgba(15,23,42,.88); color: #fff; border: 0;',
        '  border-radius: 4px; padding: 6px 10px; font-size: 12px;',
        '  font-family: sans-serif; line-height: 1.2; box-shadow: 0 1px 4px rgba(0,0,0,.25);',
        '}',
        '.mce-proppik-embed .mce-proppik-edit-btn:hover { background: rgba(37,99,235,.95); }',
        '.mce-proppik-embed iframe { pointer-events: auto !important; }',
        '.mce-proppik-embed .proppik-embed-overlay { pointer-events: auto !important; }',
    ].join('\n');
    doc.head.appendChild(style);
}

function _normalizeProppikTourEmbeds(editor) {
    if (!editor || !editor.getBody) {
        return;
    }

    var body = editor.getBody();
    Array.prototype.slice.call(body.querySelectorAll('div')).forEach(function (el) {
        if (!_isProppikTourEmbedRoot(el)) {
            return;
        }
        // Prefer the outermost matching tour root (do not mark nested wrappers twice).
        var parent = el.parentElement;
        while (parent && parent !== body) {
            if (_isProppikTourEmbedRoot(parent)) {
                return;
            }
            parent = parent.parentElement;
        }
        _markProppikTourEmbed(el);
    });

    _stripSandboxFromEmbedIframes(editor);
}

function _cleanProppikTourEmbedHtml(html) {
    if (!html) {
        return '';
    }
    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    wrap.querySelectorAll('[data-mce-proppik-embed]').forEach(function (el) {
        el.removeAttribute('data-mce-proppik-embed');
        el.removeAttribute('contenteditable');
        el.removeAttribute('data-mce-resize');
        el.removeAttribute('data-mce-selected');
        el.removeAttribute('title');
        el.classList.remove('mce-proppik-embed');
        if (!el.getAttribute('class')) {
            el.removeAttribute('class');
        }
    });
    wrap.querySelectorAll('.mce-proppik-edit-btn, [data-mce-proppik-chrome]').forEach(function (el) {
        if (el.parentNode) {
            el.parentNode.removeChild(el);
        }
    });
    wrap.querySelectorAll('iframe').forEach(function (iframe) {
        _scrubIframeEditorStyles(iframe);
    });
    // Strip TinyMCE runtime attrs that should not ship to frontend.
    wrap.querySelectorAll('[data-mce-style]').forEach(function (el) {
        el.removeAttribute('data-mce-style');
    });
    wrap.querySelectorAll('[data-mce-selected]').forEach(function (el) {
        el.removeAttribute('data-mce-selected');
    });
    return wrap.innerHTML;
}

function _findProppikTourEmbedFromNode(node, body) {
    var el = node;
    if (el && el.nodeType === 3) {
        el = el.parentElement;
    }
    while (el && el !== body) {
        if (_isProppikTourEmbedRoot(el) || (el.getAttribute && el.getAttribute('data-mce-proppik-embed') === '1')) {
            return el;
        }
        el = el.parentElement;
    }
    return null;
}

function _openProppikTourEmbedEditor(editor, embedEl) {
    if (!editor || !embedEl) {
        return;
    }

    var currentHtml = _cleanProppikTourEmbedHtml(embedEl.outerHTML);

    editor.windowManager.open({
        title: 'Edit Tour Embed',
        size: 'large',
        body: {
            type: 'panel',
            items: [
                {
                    type: 'htmlpanel',
                    html: '<p style="margin:0 0 8px;color:#666;font-size:13px;">Paste or edit the full PROP PIK embed HTML (wrapper, overlay, and iframe).</p>',
                },
                {
                    type: 'textarea',
                    name: 'code',
                    label: 'Embed code',
                    maximized: true,
                },
            ],
        },
        buttons: [
            {
                type: 'custom',
                name: 'remove',
                text: 'Remove embed',
                borderless: true,
            },
            {
                type: 'cancel',
                text: 'Cancel',
            },
            {
                type: 'submit',
                text: 'Save',
                primary: true,
            },
        ],
        initialData: {
            code: currentHtml,
        },
        onAction: function (api, details) {
            if (details.name !== 'remove') {
                return;
            }
            editor.undoManager.transact(function () {
                editor.dom.remove(embedEl);
                editor.nodeChanged();
                editor.save();
            });
            api.close();
        },
        onSubmit: function (api) {
            var code = String(api.getData().code || '').trim();
            if (!code) {
                editor.windowManager.alert('Embed code cannot be empty. Use Remove embed to delete it.');
                return;
            }
            editor.undoManager.transact(function () {
                editor.dom.setOuterHTML(embedEl, code);
                _normalizeProppikTourEmbeds(editor);
                editor.nodeChanged();
                editor.save();
            });
            api.close();
        },
    });
}

function _getSelectedEditorImage(editor) {
    if (!editor || !editor.selection) {
        return null;
    }
    var node = editor.selection.getNode();
    if (!node) {
        return null;
    }
    if (node.nodeName === 'IMG') {
        return node;
    }
    if (node.nodeName === 'FIGURE') {
        return node.querySelector('img');
    }
    if (node.nodeName === 'A' && node.firstElementChild && node.firstElementChild.nodeName === 'IMG') {
        return node.firstElementChild;
    }
    return null;
}

function _parseImageTransformState(img) {
    var rot = parseInt(img.getAttribute('data-img-rotate') || '0', 10);
    if (isNaN(rot)) {
        rot = 0;
    }
    rot = ((rot % 360) + 360) % 360;
    return {
        rotate: rot,
        flipX: img.getAttribute('data-img-flip-x') === '1' ? -1 : 1,
        flipY: img.getAttribute('data-img-flip-y') === '1' ? -1 : 1,
    };
}

function _applyImageTransformState(img, state) {
    var rotate = ((state.rotate % 360) + 360) % 360;
    var flipX = state.flipX < 0 ? -1 : 1;
    var flipY = state.flipY < 0 ? -1 : 1;
    if (rotate) {
        img.setAttribute('data-img-rotate', String(rotate));
    } else {
        img.removeAttribute('data-img-rotate');
    }
    if (flipX < 0) {
        img.setAttribute('data-img-flip-x', '1');
    } else {
        img.removeAttribute('data-img-flip-x');
    }
    if (flipY < 0) {
        img.setAttribute('data-img-flip-y', '1');
    } else {
        img.removeAttribute('data-img-flip-y');
    }
    if (rotate || flipX < 0 || flipY < 0) {
        img.style.transform = 'rotate(' + rotate + 'deg) scale(' + flipX + ', ' + flipY + ')';
    } else {
        img.style.transform = '';
        if (img.getAttribute('style') === '') {
            img.removeAttribute('style');
        }
    }
}

function _mutateSelectedImageTransform(editor, mutator) {
    var img = _getSelectedEditorImage(editor);
    if (!img) {
        return;
    }
    editor.undoManager.transact(function () {
        var state = _parseImageTransformState(img);
        mutator(state);
        _applyImageTransformState(img, state);
        editor.selection.select(img);
        editor.nodeChanged();
        editor.save();
    });
}

function _getImageAlign(img) {
    if (!img) {
        return 'default';
    }
    var marked = String(img.getAttribute('data-img-align') || '').toLowerCase();
    if (marked === 'left' || marked === 'right' || marked === 'center') {
        return marked;
    }
    if (marked === 'default' || marked === 'none') {
        return 'default';
    }
    var floatVal = String(img.style.float || img.getAttribute('align') || '').toLowerCase();
    if (floatVal === 'left') {
        return 'left';
    }
    if (floatVal === 'right') {
        return 'right';
    }
    var display = String(img.style.display || '').toLowerCase();
    var marginLeft = String(img.style.marginLeft || '');
    var marginRight = String(img.style.marginRight || '');
    if (display === 'block' && marginLeft === 'auto' && marginRight === 'auto') {
        return 'center';
    }
    return 'default';
}

function _clearImageAlignStyles(editor, img) {
    editor.dom.setAttrib(img, 'align', null);
    editor.dom.setAttrib(img, 'data-img-align', null);
    editor.dom.setStyles(img, {
        float: '',
        display: '',
        marginLeft: '',
        marginRight: '',
        marginTop: '',
        marginBottom: '',
        textAlign: '',
    });
    // Clear parent text-align leftovers from TinyMCE default justify commands.
    var parent = editor.dom.getParent(img, 'p,div,figure,td,th');
    if (parent && parent !== editor.getBody()) {
        var parentAlign = String(editor.dom.getStyle(parent, 'text-align') || '').toLowerCase();
        if (parentAlign === 'left' || parentAlign === 'right' || parentAlign === 'center' || parentAlign === 'justify') {
            editor.dom.setStyle(parent, 'text-align', '');
        }
        editor.dom.setAttrib(parent, 'align', null);
        editor.dom.setAttrib(parent, 'data-mce-style', null);
    }
}

function _alignSelectedImage(editor, align) {
    var img = _getSelectedEditorImage(editor);
    if (!img) {
        return;
    }
    var nextAlign = align || 'default';
    editor.undoManager.transact(function () {
        // Always wipe previous alignment first so only one mode is active.
        _clearImageAlignStyles(editor, img);

        if (nextAlign === 'left') {
            editor.dom.setAttrib(img, 'data-img-align', 'left');
            editor.dom.setStyles(img, {
                float: 'left',
                display: 'inline',
                marginLeft: '0',
                marginRight: '1rem',
                marginBottom: '0.5rem',
            });
        } else if (nextAlign === 'right') {
            editor.dom.setAttrib(img, 'data-img-align', 'right');
            editor.dom.setStyles(img, {
                float: 'right',
                display: 'inline',
                marginLeft: '1rem',
                marginRight: '0',
                marginBottom: '0.5rem',
            });
        } else if (nextAlign === 'center') {
            editor.dom.setAttrib(img, 'data-img-align', 'center');
            editor.dom.setStyles(img, {
                float: 'none',
                display: 'block',
                marginLeft: 'auto',
                marginRight: 'auto',
                marginBottom: '0.5rem',
            });
        } else {
            // Default: normal flow, no float / no forced margins.
            editor.dom.setAttrib(img, 'data-img-align', 'default');
            editor.dom.setStyles(img, {
                float: 'none',
                display: 'inline-block',
                marginLeft: '',
                marginRight: '',
                marginBottom: '',
                maxWidth: '100%',
                height: 'auto',
            });
        }

        editor.selection.select(img);
        editor.nodeChanged();
        editor.save();
    });
}

function _bindImageAlignToggle(editor, name, alignValue, icon, tooltip) {
    editor.ui.registry.addToggleButton(name, {
        icon: icon,
        tooltip: tooltip,
        onAction: function () {
            _alignSelectedImage(editor, alignValue);
        },
        onSetup: function (api) {
            var sync = function () {
                var img = _getSelectedEditorImage(editor);
                api.setActive(!!img && _getImageAlign(img) === alignValue);
            };
            editor.on('NodeChange', sync);
            sync();
            return function () {
                editor.off('NodeChange', sync);
            };
        },
    });
}

function _setSelectedImageWidth(editor, widthValue) {
    var img = _getSelectedEditorImage(editor);
    if (!img) {
        return;
    }
    editor.undoManager.transact(function () {
        if (!widthValue) {
            editor.dom.setAttrib(img, 'width', null);
            editor.dom.setStyle(img, 'width', '');
            editor.dom.setStyle(img, 'height', 'auto');
            editor.dom.setStyle(img, 'maxWidth', '100%');
        } else {
            editor.dom.setAttrib(img, 'width', null);
            editor.dom.setStyle(img, 'width', widthValue);
            editor.dom.setStyle(img, 'height', 'auto');
            editor.dom.setStyle(img, 'maxWidth', '100%');
        }
        editor.selection.select(img);
        editor.nodeChanged();
        editor.save();
    });
}

function _openSelectedImageOptions(editor) {
    var img = _getSelectedEditorImage(editor);
    if (!img) {
        return;
    }
    editor.selection.select(img);
    editor.execCommand('mceImage');
}

function _replaceSelectedImageFromLibrary(editor, modalId) {
    var img = _getSelectedEditorImage(editor);
    if (!img || !modalId) {
        return;
    }
    _openEditorMediaLibrary(editor, modalId, {
        forcedType: 'image',
        title: 'Replace Image',
        setButtonText: 'Replace',
        onImageSelected: function (item) {
            if (!item || !item.url) {
                return;
            }
            editor.undoManager.transact(function () {
                editor.dom.setAttrib(img, 'src', item.url);
                if (item.alt_text) {
                    editor.dom.setAttrib(img, 'alt', item.alt_text);
                }
                if (item.title) {
                    editor.dom.setAttrib(img, 'title', item.title);
                }
                editor.selection.select(img);
                editor.nodeChanged();
                editor.save();
            });
        },
    });
}

function _registerCustomImageToolbar(editor, opts) {
    editor.ui.registry.addIcon('img-rotate-left',
        '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M7.11 8.53L5.7 7.11C4.65 8.39 4 10.11 4 12c0 3.87 3.13 7 7 7h.5v-2H11c-2.76 0-5-2.24-5-5 0-1.47.64-2.78 1.64-3.71l1.47 1.47L13 7.17V3H7.17l2.94 2.94zM20 12h-2c0-2.76-2.24-5-5-5h-.5v-2H13c3.87 0 7 3.13 7 7z"/></svg>');
    editor.ui.registry.addIcon('img-rotate-right',
        '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M15.55 5.55L13.61 3.5 11.67 5.55 13 6.88V11h2V7.76l1.45 1.45C17.4 10.11 18 11.49 18 13c0 2.76-2.24 5-5 5h-.5v2H13c3.87 0 7-3.13 7-7 0-1.89-.65-3.61-1.7-4.89l-1.47 1.44zM7 13H5c0 3.87 3.13 7 7 7h.5v-2H12c-2.76 0-5-2.24-5-5z"/></svg>');
    editor.ui.registry.addIcon('img-flip-h',
        '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M15 21h2v-2h-2v2zm4-12h2V7h-2v2zM3 5v14c0 1.1.9 2 2 2h4v-2H5V5h4V3H5c-1.1 0-2 .9-2 2zm16-2v2h2c0-1.1-.9-2-2-2zm-8 20h2V1h-2v22zm8-6h2v-2h-2v2zM15 5h2V3h-2v2zm4 8h2v-2h-2v2zm0 8c1.1 0 2-.9 2-2h-2v2z"/></svg>');
    editor.ui.registry.addIcon('img-flip-v',
        '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M3 15h2v2H3v-2zm12 4h2v2h-2v-2zM7 3v2h2V3H7zM3 19c0 1.1.9 2 2 2v-2H3zm16-4h2v2h-2v-2zm0-8h2v2h-2V7zm-8 16h2v-2h2v-2h-2v-2h-2v2H9v2h2v2zm8-20v2h2c0-1.1-.9-2-2-2zM3 7h2v2H3V7zm0 4h2v2H3v-2zm8-8H9v2h2V3zM7 19h2v2H7v-2zM19 3h-4v2h4v4h2V5c0-1.1-.9-2-2-2zm0 16v-2h2c0 1.1-.9 2-2 2z"/></svg>');

    editor.ui.registry.addButton('imageRotateLeft', {
        icon: 'img-rotate-left',
        tooltip: 'Rotate counterclockwise',
        onAction: function () {
            _mutateSelectedImageTransform(editor, function (state) {
                state.rotate -= 90;
            });
        },
    });
    editor.ui.registry.addButton('imageRotateRight', {
        icon: 'img-rotate-right',
        tooltip: 'Rotate clockwise',
        onAction: function () {
            _mutateSelectedImageTransform(editor, function (state) {
                state.rotate += 90;
            });
        },
    });
    editor.ui.registry.addButton('imageFlipH', {
        icon: 'img-flip-h',
        tooltip: 'Flip horizontal',
        onAction: function () {
            _mutateSelectedImageTransform(editor, function (state) {
                state.flipX *= -1;
            });
        },
    });
    editor.ui.registry.addButton('imageFlipV', {
        icon: 'img-flip-v',
        tooltip: 'Flip vertical',
        onAction: function () {
            _mutateSelectedImageTransform(editor, function (state) {
                state.flipY *= -1;
            });
        },
    });

    editor.ui.registry.addButton('imageOptions', {
        icon: 'image',
        tooltip: 'Image options (size, alt, title)',
        onAction: function () {
            _openSelectedImageOptions(editor);
        },
    });

    if (opts.mediaModalId) {
        editor.ui.registry.addIcon('img-replace',
            '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>');
        editor.ui.registry.addButton('imageReplace', {
            icon: 'img-replace',
            tooltip: 'Replace image from Media Library',
            onAction: function () {
                _replaceSelectedImageFromLibrary(editor, opts.mediaModalId);
            },
        });
    }

    editor.ui.registry.addIcon('img-size',
        '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M9 9H3v6h2v-2h2v2h2V9zm12 6h-6V9h2v2h2V9h2v6zM9 3v6H7V5H5v2H3V3h6zm12 0v6h-2V5h-2v2h-2V3h6z"/></svg>');
    editor.ui.registry.addIcon('img-remove',
        '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>');

    editor.ui.registry.addMenuButton('imageSizeMenu', {
        icon: 'img-size',
        tooltip: 'Image size',
        fetch: function (callback) {
            callback([
                {
                    type: 'menuitem',
                    text: 'Original / Auto',
                    onAction: function () { _setSelectedImageWidth(editor, ''); },
                },
                {
                    type: 'menuitem',
                    text: '25% width',
                    onAction: function () { _setSelectedImageWidth(editor, '25%'); },
                },
                {
                    type: 'menuitem',
                    text: '50% width',
                    onAction: function () { _setSelectedImageWidth(editor, '50%'); },
                },
                {
                    type: 'menuitem',
                    text: '75% width',
                    onAction: function () { _setSelectedImageWidth(editor, '75%'); },
                },
                {
                    type: 'menuitem',
                    text: '100% width',
                    onAction: function () { _setSelectedImageWidth(editor, '100%'); },
                },
                {
                    type: 'separator',
                },
                {
                    type: 'menuitem',
                    text: 'Custom size…',
                    onAction: function () { _openSelectedImageOptions(editor); },
                },
            ]);
        },
    });

    editor.ui.registry.addIcon('img-align-default',
        '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M3 5h18v2H3V5zm4 4h10v2H7V9zm-4 4h18v2H3v-2zm4 4h10v2H7v-2z"/></svg>');

    _bindImageAlignToggle(editor, 'imageAlignDefault', 'default', 'img-align-default', 'Align default');
    _bindImageAlignToggle(editor, 'imageAlignLeft', 'left', 'align-left', 'Align left');
    _bindImageAlignToggle(editor, 'imageAlignCenter', 'center', 'align-center', 'Align center');
    _bindImageAlignToggle(editor, 'imageAlignRight', 'right', 'align-right', 'Align right');

    editor.ui.registry.addButton('imageRemove', {
        icon: 'img-remove',
        tooltip: 'Remove image',
        onAction: function () {
            var img = _getSelectedEditorImage(editor);
            if (!img) {
                return;
            }
            editor.undoManager.transact(function () {
                var figure = editor.dom.getParent(img, 'figure');
                editor.dom.remove(figure || img);
                editor.nodeChanged();
                editor.save();
            });
        },
    });

    var imageToolbarItems = [
        'imageRotateLeft imageRotateRight imageFlipH imageFlipV',
        'imageOptions' + (opts.mediaModalId ? ' imageReplace' : '') + ' imageSizeMenu',
        'imageAlignDefault imageAlignLeft imageAlignCenter imageAlignRight',
        'imageRemove',
    ].join(' | ');

    editor.ui.registry.addContextToolbar('customImageToolbar', {
        predicate: function (node) {
            return !!node && node.nodeName === 'IMG' && !editor.dom.getAttrib(node, 'data-mce-object') && !editor.dom.getAttrib(node, 'data-mce-placeholder');
        },
        items: imageToolbarItems,
        position: 'node',
        scope: 'node',
    });
}

window.initRichEditor = function (options) {
    var opts = Object.assign({
        editorId: 'rich-editor',
        height: 400,
        placeholder: '',
        showWordCount: false,
        wordCountElId: null,
        mediaModalId: 'editorContentMediaModal',
        enableIconPicker: true,
        minWords: 0,
        editorType: 'full',
    }, options || {});
    var requestedEditorType = (options && (options.editorType || options.editorSize || options.toolbarMode || options.variant)) || opts.editorType;
    var editorType = _normalizeRichEditorPreset(requestedEditorType);

    if (opts.mediaModalId === false) {
        opts.mediaModalId = null;
    }

    // Derive TinyMCE asset base from the global appConfig set in footer-scripts.blade.php
    var baseUrl    = (window.appConfig && window.appConfig.baseUrl) ? window.appConfig.baseUrl : '';
    var skinUrl    = baseUrl + '/admin/js/tinymce/skins/ui/oxide';
    var contentCss = baseUrl + '/admin/js/tinymce/skins/content/default/content.min.css';

    // Row 4 — insert tools (built dynamically based on options)
    // Keep image custom, but use TinyMCE's built-in media dialog for video/embed.
    // TinyMCE 7 registers built-in buttons after setup, so only the image button
    // uses a custom name to avoid being clobbered.
    var toolbar4Parts = ['link'];
    if (opts.mediaModalId) {
        toolbar4Parts.push('media', 'mlimage');
    }
    if (opts.enableIconPicker) {
        toolbar4Parts.push('insertIcon');
    }
    toolbar4Parts.push('codesample');
    var toolbar4 = toolbar4Parts.join(' ') || 'link codesample';
    var toolbar1 = 'undo redo | formatMenu headingMenu bold italic underline strikethrough | forecolor backcolor |   alignleft aligncenter alignright alignjustify | bullist numlist ';
    var toolbar2 = ' outdent indent | removeformat' + ' ' + toolbar4 + ' ' + 'table hr charmap | preview code fullscreen';
    var mediumToolbar1 = 'undo redo | formatMenu headingMenu  bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist ';
    var mediumToolbar2 = ' forecolor backcolor | outdent indent | removeformat' + ' ' + toolbar4 + ' ' + 'table hr charmap | preview code fullscreen';
    var editorPresets = {
        full: {
            menubar: 'edit view insert format tools table help',
            toolbar1: toolbar1,
            toolbar2: toolbar2,
        },
        medium: {
            menubar: false,
            toolbar1: mediumToolbar1,
            toolbar2: mediumToolbar2,
        },
        small: {
            menubar: false,
            toolbar: 'undo redo | blocks formatMenu bold italic underline strikethrough | bullist numlist | ' + toolbar4 + ' |removeformat code fullscreen',
            blockFormats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Blockquote=blockquote; Preformatted=pre',
            fontFamilyFormats: null,
            fontSizeFormats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
        },
    };
    var editorPreset = editorPresets[editorType] || editorPresets.full;

    // Keep TinyMCE's full default font list and only prepend Manrope as default.
    var richEditorFontFamilyFormats = [
        'Manrope=Manrope, sans-serif',
        'Andale Mono=andale mono,monospace',
        'Arial=arial,helvetica,sans-serif',
        'Arial Black=arial black,sans-serif',
        'Book Antiqua=book antiqua,palatino,serif',
        'Comic Sans MS=comic sans ms,sans-serif',
        'Courier New=courier new,courier,monospace',
        'Georgia=georgia,palatino,serif',
        'Helvetica=helvetica,arial,sans-serif',
        'Impact=impact,sans-serif',
        'Inter=Inter, sans-serif',
        'Open Sans=Open Sans, sans-serif',
        'Poppins=Poppins, sans-serif',
        'Roboto=Roboto, sans-serif',
        'Symbol=symbol',
        'Tahoma=tahoma,arial,helvetica,sans-serif',
        'Terminal=terminal,monaco,monospace',
        'Times New Roman=times new roman,times,serif',
        'Trebuchet MS=trebuchet ms,geneva,sans-serif',
        'Verdana=verdana,geneva,sans-serif',
        'Webdings=webdings',
        'Wingdings=wingdings,zapf dingbats',
    ].join(';');

    var setupFn = function (editor) {

        // ── Custom heading/block dropdown ───────────────────────────────────
        editor.ui.registry.addMenuButton('headingMenu', {
            text: 'Heading',
            tooltip: 'Paragraph and headings',
            fetch: function (callback) {
                callback([
                    {
                        type: 'menuitem',
                        text: 'Paragraph',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'p'); },
                    },
                    {
                        type: 'separator',
                    },
                    {
                        type: 'menuitem',
                        text: 'Heading 1',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'h1'); },
                    },
                    {
                        type: 'menuitem',
                        text: 'Heading 2',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'h2'); },
                    },
                    {
                        type: 'menuitem',
                        text: 'Heading 3',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'h3'); },
                    },
                    {
                        type: 'menuitem',
                        text: 'Heading 4',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'h4'); },
                    },
                    {
                        type: 'menuitem',
                        text: 'Heading 5',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'h5'); },
                    },
                    {
                        type: 'menuitem',
                        text: 'Heading 6',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'h6'); },
                    },
                    {
                        type: 'separator',
                    },
                    {
                        type: 'menuitem',
                        text: 'Quote',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'blockquote'); },
                    },
                    {
                        type: 'menuitem',
                        text: 'Preformatted',
                        onAction: function () { editor.execCommand('FormatBlock', false, 'pre'); },
                    },
                ]);
            },
        });

        // Custom toolbar menu for presets without the top format menubar.
        editor.ui.registry.addMenuButton('formatMenu', {
            text: 'Format',
            tooltip: 'Formatting options',
            fetch: function (callback) {
                callback(_buildRichEditorFormatMenuItems());
            },
        });

        // ── Word count ──────────────────────────────────────────────────────
        if (opts.showWordCount) {
            var wcElId  = opts.wordCountElId || (opts.editorId + '-word-count');
            var minW    = opts.minWords || 0;

            function updateWordCount() {
                var text  = editor.getContent({ format: 'text' });
                var count = text.trim() ? text.trim().split(/\s+/).length : 0;
                var el    = document.getElementById(wcElId);
                if (!el) return;
                el.textContent = count;
                var wrap = el.closest('.rich-editor-word-count');
                if (wrap && minW > 0) {
                    wrap.classList.toggle('text-danger', count < minW);
                    wrap.classList.toggle('text-muted',  count >= minW);
                }
            }

            editor.on('init',   updateWordCount);
            editor.on('change', updateWordCount);
            editor.on('input',  updateWordCount);
        }

        // ── Custom image media-library toolbar button ───────────────────────
        if (opts.mediaModalId) {
            editor.ui.registry.addButton('mlimage', {
                icon: 'image',
                tooltip: 'Insert Image from Media Library',
                onAction: function () {
                    _openEditorMediaLibrary(editor, opts.mediaModalId, { forcedType: 'image', title: 'Insert Image' });
                },
            });
            editor.ui.registry.addMenuItem('mlimage', {
                icon: 'image',
                text: 'Image from Media Library',
                onAction: function () {
                    _openEditorMediaLibrary(editor, opts.mediaModalId, { forcedType: 'image', title: 'Insert Image' });
                },
            });
        }

        // Floating image toolbar (premium-demo style): rotate / flip / options / size / align.
        _registerCustomImageToolbar(editor, opts);

        // ── Icon picker toolbar button ──────────────────────────────────────
        if (opts.enableIconPicker) {
            editor.ui.registry.addIcon('icon-picker',
                '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">' +
                '<path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24' +
                '.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1' +
                ' 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976' +
                '-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>' +
                '</svg>'
            );
            editor.ui.registry.addButton('insertIcon', {
                icon: 'icon-picker',
                tooltip: 'Insert Icon',
                onAction: function () { _openEditorIconPicker(editor); },
            });
            editor.ui.registry.addMenuItem('insertIcon', {
                text: 'Icon',
                icon: 'emoji',
                onAction: function () { _openEditorIconPicker(editor); },
            });
        }

        // ── Restore old/initial content on init ─────────────────────────────
        editor.on('init', function () {
            editor.on('change', function () { editor.save(); });

            // TinyMCE's built-in preview uses a sandboxed dialog iframe, which blocks nested
            // embed iframes (e.g. Prop Pik tours). Open a real browser window instead.
            editor.addCommand('mcePreview', function () {
                _openRichEditorPreview(editor);
            });

            _injectProppikEditorOnlyStyles(editor);
            _normalizeProppikTourEmbeds(editor);
            // TinyMCE media may swap iframes for placeholders slightly after insert.
            window.setTimeout(function () {
                _normalizeProppikTourEmbeds(editor);
            }, 150);

            // Tour stays interactive. Overlay click = start tour. Edit button = edit/remove dialog.
            editor.getDoc().addEventListener('mousedown', function (e) {
                var target = e.target;
                if (!target) {
                    return;
                }
                if (target.classList && target.classList.contains('mce-proppik-edit-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                if (target.classList && target.classList.contains('proppik-embed-overlay')) {
                    // Let the overlay receive the click; don't let TinyMCE steal selection.
                    e.stopPropagation();
                }
            }, true);

            editor.getDoc().addEventListener('click', function (e) {
                var target = e.target;
                if (!target) {
                    return;
                }
                if (target.classList && target.classList.contains('mce-proppik-edit-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    var embedFromBtn = _findProppikTourEmbedFromNode(target, editor.getBody());
                    if (embedFromBtn) {
                        _openProppikTourEmbedEditor(editor, embedFromBtn);
                    }
                    return;
                }
                if (target.classList && target.classList.contains('proppik-embed-overlay')) {
                    target.style.display = 'none';
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);

            editor.getDoc().addEventListener('dblclick', function (e) {
                var target = e.target;
                if (!target) {
                    return;
                }
                // Double-click Edit button or overlay opens the edit/remove dialog.
                var isEditChrome = target.classList && (
                    target.classList.contains('mce-proppik-edit-btn') ||
                    target.classList.contains('proppik-embed-overlay')
                );
                if (!isEditChrome) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                var embed = _findProppikTourEmbedFromNode(target, editor.getBody());
                if (embed) {
                    _openProppikTourEmbedEditor(editor, embed);
                }
            }, true);
        });

        editor.on('SetContent', function () {
            _injectProppikEditorOnlyStyles(editor);
            _normalizeProppikTourEmbeds(editor);
            window.setTimeout(function () {
                _normalizeProppikTourEmbeds(editor);
            }, 150);
        });

        // Keep tour embeds as one selectable block after media/paste inserts.
        editor.on('ExecCommand', function (e) {
            var cmd = String(e.command || '');
            if (cmd === 'mceMedia' || cmd === 'mceInsertContent' || cmd === 'mceInsertRawHTML') {
                window.setTimeout(function () {
                    _normalizeProppikTourEmbeds(editor);
                }, 0);
                window.setTimeout(function () {
                    _normalizeProppikTourEmbeds(editor);
                }, 150);
            }
        });

        // Strip editor-only attrs so saved HTML matches the original tour embed.
        editor.on('GetContent', function (e) {
            if (!e.content) {
                return;
            }
            e.content = _cleanProppikTourEmbedHtml(e.content);
        });

        // Media toolbar while caret/selection is on a tour → open full embed editor.
        editor.on('BeforeExecCommand', function (e) {
            if (String(e.command || '') !== 'mceMedia') {
                return;
            }
            var embed = _findProppikTourEmbedFromNode(editor.selection.getNode(), editor.getBody());
            if (!embed) {
                return;
            }
            e.preventDefault();
            _openProppikTourEmbedEditor(editor, embed);
        });

        // ── Shift+Enter + optional Delete when tour root is selected ────────
        // Default TinyMCE behaviour inserts a <br> inside the same block tag,
        // which produces malformed HTML like <h1>text<br>more text</h1>.
        // This handler intercepts Shift+Enter and uses InsertParagraph instead,
        // so the cursor cleanly exits the heading/paragraph into a new <p>.
        editor.on('keydown', function (e) {
            if ((e.key === 'Delete' || e.key === 'Backspace' || e.keyCode === 46 || e.keyCode === 8)) {
                var selected = editor.selection.getNode();
                if (selected && selected.getAttribute && selected.getAttribute('data-mce-proppik-embed') === '1') {
                    e.preventDefault();
                    editor.undoManager.transact(function () {
                        editor.dom.remove(selected);
                        editor.nodeChanged();
                        editor.save();
                    });
                    return;
                }
            }

            if (e.key === 'Enter' && e.shiftKey) {
                e.preventDefault();
                var node = editor.selection.getNode();
                var blockTags = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'BLOCKQUOTE', 'PRE', 'LI'];
                if (blockTags.indexOf(node.nodeName) !== -1) {
                    editor.execCommand('InsertParagraph');
                } else {
                    editor.execCommand('InsertLineBreak');
                }
            }
        });
    };

    var initOptions = {
        selector: '#' + opts.editorId,
        height: opts.height,
        placeholder: opts.placeholder,
        skin: 'oxide',
        skin_url: skinUrl,
        content_css: [
            contentCss,
            'https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Inter:wght@400;500;600;700&family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
        ],
        menubar: editorPreset.menubar,
        menu: {
            insert: {
                title: 'Insert',
                items: (opts.mediaModalId ? 'link mlimage media insertIcon codesample inserttable charmap hr pagebreak' : 'link media insertIcon codesample inserttable charmap hr pagebreak'),
            },
        },
        plugins: [
            'preview', 'searchreplace', 'autolink', 'directionality',
            'visualblocks', 'visualchars', 'fullscreen', 'image', 'link',
            'media', 'codesample', 'table', 'charmap', 'hr', 'pagebreak',
            'nonbreaking', 'anchor', 'insertdatetime', 'advlist', 'lists',
            'wordcount', 'help', 'code', 'quickbars',
        ].join(' '),
        font_family_formats: richEditorFontFamilyFormats,
        font_formats: richEditorFontFamilyFormats,
        content_style: [
            "@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Inter:wght@400;500;600;700&family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap');",
            "@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');",
            'body { font-family: "Manrope", sans-serif;',
            '       font-size: 14px; line-height: 1.6; margin: 0; padding: 16px; }',
            'p { margin: 0 0 1rem; }',
            'p:last-child { margin-bottom: 0; }',
            '.mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before {',
            '       color: #6c757d; left: 16px; top: 16px; padding-left: 16px; }',
            '.mce-content-body.mce-edit-focus[data-mce-placeholder]:not(.mce-visualblocks)::before {',
            '       opacity: 0; }',
            'blockquote { border-left: 4px solid #ccc; margin: 1em 0; padding-left: 1em; color: #555; font-style: italic; }',
            'pre { background: #f4f4f4; padding: 1em; overflow-x: auto; }',
            'h1, h2, h3, h4, h5, h6 { font-family: "Manrope", sans-serif; }',
            'h1 { font-size: 2em; } h2 { font-size: 1.5em; } h3 { font-size: 1.17em; }',
            'h4 { font-size: 1em; } h5 { font-size: 0.83em; } h6 { font-size: 0.75em; }',
            '.material-symbols-outlined { font-family: "Material Symbols Outlined"; font-weight: normal; font-style: normal; font-size: 1.25em; line-height: 1; text-transform: none; letter-spacing: normal; word-wrap: normal; white-space: nowrap; direction: ltr; vertical-align: middle; }',
            'i[class^="ri-"], i[class*=" ri-"], i[class^="bi-"], i[class*=" bi-"] { font-size: 1.25em; vertical-align: middle; }',
            'a, a:link, a:visited, a:hover, a:active, a:focus { text-decoration: none !important; border: 0 !important; border-bottom: none !important; outline: none !important; -webkit-text-decoration: none !important; }',
            'a span { text-decoration: none !important; border: 0 !important; border-bottom: none !important; -webkit-text-decoration: none !important; }',
            // Keep explicitly white email CTA / footer links readable in the editor
            'a[style*="ffffff"], a[style*="FFFFFF"], a[style*="#fff"], a[style*="#FFF"] { color: #ffffff !important; }',
            'a[style*="ffffff"] span, a[style*="FFFFFF"] span, a[style*="#fff"] span, a[style*="#FFF"] span { color: #ffffff !important; }',
            'td[bgcolor="#2b318d"] a, td[style*="2b318d"] a, td[bgcolor="#232872"] a, td[style*="232872"] a { color: #ffffff !important; }',
            'td[bgcolor="#2b318d"] a span, td[style*="2b318d"] a span, td[bgcolor="#232872"] a span, td[style*="232872"] a span { color: #ffffff !important; }',
            'div[style*="min-height"] iframe { min-height: 500px; }',
            // Tour embed outline only — interaction stays on the live tour / overlay.
            '.mce-proppik-embed { outline: 1px dashed #94a3b8; outline-offset: 2px; }',
            'img { max-width: 100%; height: auto; }',
            'img[data-img-align="default"] { float: none; display: inline-block; margin-left: 0; margin-right: 0; }',
            'img[data-img-align="left"] { float: left; display: inline; margin: 0 1rem 0.5rem 0; }',
            'img[data-img-align="right"] { float: right; display: inline; margin: 0 0 0.5rem 1rem; }',
            'img[data-img-align="center"] { float: none; display: block; margin: 0 auto 0.5rem; }',
        ].join(' ') + (opts.contentStyle ? (' ' + opts.contentStyle) : ''),
        style_formats: [
            { title: 'Paragraph',    format: 'p' },
            { title: 'Heading 1',    format: 'h1' },
            { title: 'Heading 2',    format: 'h2' },
            { title: 'Heading 3',    format: 'h3' },
            { title: 'Heading 4',    format: 'h4' },
            { title: 'Heading 5',    format: 'h5' },
            { title: 'Heading 6',    format: 'h6' },
            { title: 'Blockquote',   format: 'blockquote' },
            { title: 'Preformatted', format: 'pre' },
        ],
        style_formats_merge: false,
        valid_elements: '*[*]',
        extended_valid_elements: [
            'script[src|async|defer|type|charset]',
            'iframe[src|width|height|frameborder|allowfullscreen|allow|loading|style|title|sandbox|class|id]',
            'blockquote[class|data-*|cite|style]',
            'div[class|data-*|style|onclick|id|title|role|aria-*|contenteditable]',
            'span[class|data-*|style]',
            'a[href|target|rel|data-*]',
            'img[src|alt|width|height|style|class|loading|title|data-*]',
            'i[class|style|aria-hidden]',
            'source[src|type]',
            'video[controls|width|height|poster|preload|autoplay|loop|muted]',
            'audio[controls|preload|autoplay|loop|muted|src]',
        ].join(','),
        verify_html: false,
        convert_unsafe_embeds: false,
        sandbox_iframes: false,
        // Keep iframes live so PROP PIK 360 tours render in the editor.
        // Tour wrappers are still non-editable blocks for select / delete / edit.
        media_live_embeds: true,
        browser_spellcheck: true,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        entity_encoding: 'raw',
        contextmenu: false,
        paste_data_images: true,
        image_caption: true,
        image_advtab: true,
        image_title: true,
        image_description: true,
        image_dimensions: true,
        object_resizing: true,
        resize_img_proportional: true,
        // Disable quickbars' default image strip; we use customImageToolbar instead.
        quickbars_image_toolbar: false,
        quickbars_selection_toolbar: 'bold italic underline | quicklink',
        quickbars_insert_toolbar: false,
        //         license_key: 'gpl',
        promotion: false,
        branding: false,
        send_usage_data: false,
        disable_stats: true,
        automatic_uploads: false,
        file_picker_types: '',
        setup: setupFn,
    };

    if (editorPreset.toolbar) {
        initOptions.toolbar = editorPreset.toolbar;
    } else {
        initOptions.toolbar1 = editorPreset.toolbar1;
        initOptions.toolbar2 = editorPreset.toolbar2;
    }
    if (editorPreset.blockFormats) {
        initOptions.block_formats = editorPreset.blockFormats;
    }
    if (editorPreset.fontFamilyFormats) {
        initOptions.font_family_formats = editorPreset.fontFamilyFormats;
        initOptions.font_formats = editorPreset.fontFamilyFormats;
    } else {
        initOptions.font_family_formats = richEditorFontFamilyFormats;
        initOptions.font_formats = richEditorFontFamilyFormats;
    }
    if (editorPreset.fontSizeFormats) {
        initOptions.font_size_formats = editorPreset.fontSizeFormats;
    }

    return tinymce.init(initOptions);
};

function _normalizeRichEditorPreset(value) {
    var preset = String(value || 'full').toLowerCase().replace(/[\s_-]+/g, '');
    var aliases = {
        full: 'full',
        large: 'full',
        medium: 'medium',
        med: 'medium',
        tworow: 'medium',
        small: 'small',
        compact: 'small',
        mini: 'small',
        onerow: 'small',
    };

    return aliases[preset] || 'full';
}

function _buildRichEditorFormatMenuItems() {
    return 'bold italic underline strikethrough superscript subscript codeformat | styles blocks fontfamily fontsize align lineheight | forecolor backcolor | removeformat';
}

// ── Lazy-load media-library.js when needed ───────────────────────────────────
var mediaLibraryScriptPromise = null;

function _ensureMediaLibraryScript() {
    if (typeof window.openMediaLibrary === 'function') {
        return Promise.resolve();
    }

    if (mediaLibraryScriptPromise) {
        return mediaLibraryScriptPromise;
    }

    var baseUrl = (window.appConfig && window.appConfig.baseUrl) ? window.appConfig.baseUrl : '';
    var scriptUrl = baseUrl + '/admin/js/media-library.js';

    mediaLibraryScriptPromise = new Promise(function (resolve, reject) {
        var script = document.createElement('script');
        script.src = scriptUrl;
        script.async = true;
        script.onload = function () { resolve(); };
        script.onerror = function () {
            mediaLibraryScriptPromise = null;
            reject(new Error('Failed to load media library script'));
        };
        document.body.appendChild(script);
    });

    return mediaLibraryScriptPromise;
}

function _showEditorToast(message) {
    if (typeof window.showToast === 'function') {
        window.showToast(message, 'error');
        return;
    }
    window.alert(message);
}

// ── Insert selected media item into a TinyMCE editor instance ────────────────
function _openEditorMediaLibrary(editor, modalId, pickerOptions) {
    _ensureMediaLibraryScript().then(function () {
        if (typeof window.openMediaLibrary !== 'function') {
            _showEditorToast('Media library is not available on this page.');
            return;
        }

        var options = Object.assign({
            multiple: false,
            setButtonText: 'Insert',
            onImageSelected: function (item) {
                _insertMediaIntoEditor(editor, item);
            },
        }, pickerOptions || {});

        openMediaLibrary(modalId, options);
    }).catch(function () {
        _showEditorToast('Could not load the media library. Please refresh and try again.');
    });
}

function _insertMediaIntoEditor(editor, item) {
    var mime  = item.mime_type || '';
    var url   = item.url;
    var title = item.title || 'Media';
    var alt   = item.alt_text || title;
    var html  = '';

    if (mime.startsWith('image/')) {
        html = '<img src="' + url + '" alt="' + alt + '" title="' + title + '" data-img-align="default" style="max-width:100%;height:auto;float:none;display:inline-block;" />';
    } else if (mime.startsWith('video/')) {
        html = '<video src="' + url + '" controls style="max-width:100%;height:auto;"></video>';
    } else if (mime.startsWith('audio/')) {
        html = '<audio src="' + url + '" controls style="width:100%;"></audio>';
    } else if (mime === 'application/pdf') {
        html = '<iframe src="' + url + '" width="100%" height="600" style="border:1px solid #ddd;"></iframe>';
    } else {
        html = '<a href="' + url + '" target="_blank" download>' + (item.filename || title) + '</a>';
    }

    editor.insertContent(html);
}

function _isIconUrl(value) {
    return typeof value === 'string'
        && (value.indexOf('http://') === 0 || value.indexOf('https://') === 0 || value.indexOf('/') === 0);
}

function _escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function _buildIconHtml(library, iconName) {
    var safeName = _escapeHtml(iconName);

    if ((library === 'custom' || library === 'image' || library === 'svg') && _isIconUrl(iconName)) {
        return '<img src="' + safeName + '" alt="" style="width:1.25em;height:1.25em;vertical-align:middle;" />';
    }

    if (library === 'material-symbols') {
        return '<span class="material-symbols-outlined">' + safeName + '</span>';
    }

    if (library === 'bootstrap' && iconName.indexOf('bi-') === 0 && iconName.indexOf('bi bi-') !== 0) {
        return '<i class="bi ' + safeName + '"></i>';
    }

    return '<i class="' + safeName + '"></i>';
}

function _insertIconIntoEditor(editor, library, iconName) {
    editor.insertContent(_buildIconHtml(library, iconName));
}

function _openEditorIconPicker(editor) {
    if (typeof window.openIconPicker !== 'function') {
        _showEditorToast('Icon picker is not available on this page.');
        return;
    }

    window.openIconPicker({
        onSelect: function (library, iconName) {
            _insertIconIntoEditor(editor, library, iconName);
        },
    });
}

/**
 * Compact rich text editor for FAQ answer fields.
 */
window.initFaqAnswerEditor = function (editorId) {
    if (!editorId || typeof window.initRichEditor !== 'function') {
        return Promise.resolve(null);
    }

    if (window.tinymce) {
        var existing = tinymce.get(editorId);
        if (existing) {
            return Promise.resolve(existing);
        }
    }

    return window.initRichEditor({
        editorId: editorId,
        editorType: 'small',
        height: 180,
        placeholder: 'Provide a clear, helpful answer...',
        mediaModalId: false,
        enableIconPicker: false,
        showWordCount: false,
    }).then(function (result) {
        return window.normalizeTinyMceEditor(result);
    });
};

window.normalizeTinyMceEditor = function (result) {
    if (Array.isArray(result)) {
        return result[0] || null;
    }

    return result || null;
};

window.getFaqAnswerHtml = function (editorId) {
    if (window.tinymce && editorId) {
        var ed = tinymce.get(editorId);
        if (ed && !ed.removed) {
            ed.save();
            return ed.getContent({ format: 'html' });
        }
    }

    var el = document.getElementById(editorId);
    return el ? el.value : '';
};

window.removeFaqAnswerEditor = function (editorId) {
    if (!window.tinymce || !editorId) {
        return;
    }

    var ed = tinymce.get(editorId);
    if (ed) {
        ed.remove();
    }
};

window.syncAllFaqAnswerEditors = function (root) {
    if (window.tinymce) {
        tinymce.triggerSave();
    }

    var scope = root;
    if (typeof root === 'string') {
        scope = document.querySelector(root);
    }
    if (!scope || !scope.querySelectorAll) {
        return;
    }

    scope.querySelectorAll('textarea.faq-answer-input').forEach(function (textarea) {
        if (!textarea.id) {
            return;
        }

        textarea.value = window.getFaqAnswerHtml(textarea.id);
    });
};

window.isFaqAnswerEditorVisible = function (row) {
    if (!row) {
        return false;
    }

    var answerInput = row.querySelector('.faq-answer-input');
    if (!answerInput) {
        return false;
    }

    var el = answerInput.parentElement;
    while (el && el !== document.body) {
        if (el.classList.contains('d-none')) {
            return false;
        }
        if (el.classList.contains('collapse') && !el.classList.contains('show')) {
            return false;
        }
        el = el.parentElement;
    }

    return true;
};

window.ensureFaqAnswerEditorForRow = function (row, options) {
    options = options || {};
    var answerInput = row.querySelector('.faq-answer-input');

    if (!answerInput || !answerInput.id || typeof window.initFaqAnswerEditor !== 'function') {
        return;
    }

    if (!window.isFaqAnswerEditorVisible(row)) {
        row.dataset.faqEditorPending = '1';
        return;
    }

    delete row.dataset.faqEditorPending;

    if (window.tinymce) {
        var existing = tinymce.get(answerInput.id);
        if (existing) {
            row.dataset.faqEditorInit = '1';
            existing.fire('ResizeEditor');
            if (typeof options.onChange === 'function' && row.dataset.faqEditorChangeBound !== '1') {
                row.dataset.faqEditorChangeBound = '1';
                existing.on('change input undo redo keyup ExecCommand', options.onChange);
            }
            if (typeof options.onBlur === 'function' && row.dataset.faqEditorBlurBound !== '1') {
                row.dataset.faqEditorBlurBound = '1';
                existing.on('blur', options.onBlur);
            }
            return;
        }
    }

    if (row.dataset.faqEditorLoading === '1') {
        return;
    }

    if (row.dataset.faqEditorInit === '1') {
        delete row.dataset.faqEditorInit;
    }

    row.dataset.faqEditorLoading = '1';

    window.initFaqAnswerEditor(answerInput.id).then(function (editor) {
        delete row.dataset.faqEditorLoading;

        if (!editor) {
            delete row.dataset.faqEditorInit;
            return;
        }

        row.dataset.faqEditorInit = '1';

        if (typeof options.onChange === 'function' && row.dataset.faqEditorChangeBound !== '1') {
            row.dataset.faqEditorChangeBound = '1';
            editor.on('change input undo redo keyup ExecCommand', options.onChange);
        }

        if (typeof options.onBlur === 'function' && row.dataset.faqEditorBlurBound !== '1') {
            row.dataset.faqEditorBlurBound = '1';
            editor.on('blur', options.onBlur);
        }

        editor.fire('ResizeEditor');
    }).catch(function () {
        delete row.dataset.faqEditorLoading;
        delete row.dataset.faqEditorInit;
    });
};

window.destroyFaqAnswerEditorForRow = function (row) {
    var answerInput = row.querySelector('.faq-answer-input');
    if (answerInput && answerInput.id && typeof window.removeFaqAnswerEditor === 'function') {
        window.removeFaqAnswerEditor(answerInput.id);
    }

    delete row.dataset.faqEditorInit;
    delete row.dataset.faqEditorPending;
    delete row.dataset.faqEditorLoading;
    delete row.dataset.faqEditorChangeBound;
    delete row.dataset.faqEditorBlurBound;
};

window.refreshFaqAnswerEditorsIn = function (root) {
    var scope = root;
    if (typeof root === 'string') {
        scope = document.querySelector(root);
    }
    if (!scope || !scope.querySelectorAll) {
        return;
    }

    scope.querySelectorAll('.faq-row, .post-faq-row, .embedded-faq-row').forEach(function (row) {
        if (!row.querySelector('.faq-answer-input')) {
            return;
        }

        if (window.isFaqAnswerEditorVisible(row) || row.dataset.faqEditorPending === '1') {
            window.ensureFaqAnswerEditorForRow(row);
        }
    });
};

window.scheduleRefreshFaqAnswerEditorsIn = function (root, delayMs) {
    var delay = typeof delayMs === 'number' ? delayMs : 80;

    setTimeout(function () {
        var scope = root;
        if (typeof root === 'string') {
            scope = document.querySelector(root);
        }
        if (!scope || !scope.querySelectorAll) {
            return;
        }

        var rows = Array.from(scope.querySelectorAll('.faq-row, .post-faq-row, .embedded-faq-row')).filter(function (row) {
            return row.querySelector('.faq-answer-input')
                && (window.isFaqAnswerEditorVisible(row) || row.dataset.faqEditorPending === '1');
        });

        rows.forEach(function (row, index) {
            setTimeout(function () {
                window.ensureFaqAnswerEditorForRow(row);
            }, index * 60);
        });
    }, delay);
};

if (!window.__faqPanelCardHook) {
    window.__faqPanelCardHook = true;
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-panel-action="collapse"]');
        if (!button) {
            return;
        }

        var card = button.closest('[data-panel-card]');
        if (!card || !card.querySelector('.faq-answer-input')) {
            return;
        }

        setTimeout(function () {
            window.scheduleRefreshFaqAnswerEditorsIn(card, 80);
        }, 50);
    });
}
