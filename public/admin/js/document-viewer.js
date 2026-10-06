/**
 * Document Viewer
 * 
 * Usage:
 *   openDocumentViewer({ url, filename, mime, title })
 *
 * Supported types (auto-detected from mime or extension):
 *   PDF          → native <iframe>  + download / print / fullscreen
 *   Image        → <img> with zoom +/−, rotate, fullscreen
 *   Office Docs  → Google Docs Viewer <iframe>  (fallback: download)
 *   TXT          → fetch → <pre>
 *   Unknown      → download fallback panel
 */
(function (window) {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    let _currentUrl      = '';
    let _currentFilename = '';
    let _currentMime     = '';
    let _currentTitle    = '';

    // image transform state
    let _zoom   = 1;
    let _rotate = 0;

    // ── Type detection ───────────────────────────────────────────────────────
    const OFFICE_TYPES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    const OFFICE_EXTS = /\.(doc|docx|xls|xlsx|ppt|pptx)$/i;
    const IMAGE_EXTS  = /\.(jpe?g|png|gif|webp|svg|bmp)$/i;
    const TXT_EXTS    = /\.(txt|csv|log|md)$/i;

    function detectType(mime, filename) {
        const m = (mime || '').toLowerCase();
        const f = (filename || '').toLowerCase();

        if (m === 'application/pdf' || f.endsWith('.pdf'))       return 'pdf';
        if (m.startsWith('image/') || IMAGE_EXTS.test(f))        return 'image';
        if (m === 'text/plain' || TXT_EXTS.test(f))              return 'txt';
        if (OFFICE_TYPES.includes(m) || OFFICE_EXTS.test(f))     return 'office';
        return 'unknown';
    }

    // ── DOM helpers ──────────────────────────────────────────────────────────
    function $id(id) { return document.getElementById(id); }

    function showLoading(show) {
        const el = $id('dvLoading');
        if (el) el.style.display = show ? 'flex' : 'none';
    }

    function setBody(html) {
        const el = $id('dvBody');
        if (el) el.innerHTML = html;
    }

    function setTitle(text) {
        const el = $id('dvTitle');
        if (el) el.textContent = text || 'Document Viewer';
    }

    function setToolbarMode(type) {
        const groups = {
            pdf:     $id('dvToolbarPdf'),
            image:   $id('dvToolbarImage'),
            generic: $id('dvToolbarGeneric'),
        };
        Object.entries(groups).forEach(([k, el]) => {
            if (el) el.style.display = (k === type) ? 'flex' : 'none';
        });
    }

    // ── Renderers ────────────────────────────────────────────────────────────
    function renderPdf(url) {
        setToolbarMode('pdf');
        setBody('<iframe id="dvPdfFrame" class="dv-pdf-frame" src="' + url + '" title="PDF Preview"></iframe>');
        showLoading(false);
    }

    function renderImage(url, filename) {
        _zoom   = 1;
        _rotate = 0;
        setToolbarMode('image');
        setBody(
            '<div class="dv-image-wrap" id="dvImageWrap">' +
            '<img id="dvImg" src="' + url + '" alt="' + (filename || '') + '" ' +
            'onload="window.DocumentViewer._onImageLoad()" onerror="window.DocumentViewer._onImageError()">' +
            '</div>'
        );
        updateImageTransform();
    }

    function renderTxt(url) {
        setToolbarMode('generic');
        setBody('<div class="dv-loading" id="dvLoading" style="display:flex"><div class="spinner-border text-light" role="status"></div><span>Loading...</span></div>');

        fetch(url)
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (text) {
                setBody('<div class="dv-txt-wrap">' + escapeHtml(text) + '</div>');
            })
            .catch(function () {
                renderError('Could not load text file.', url, _currentFilename);
            });
    }

    function renderOffice(url) {
        setToolbarMode('generic');
        const gDocsUrl = 'https://docs.google.com/gview?url=' + encodeURIComponent(url) + '&embedded=true';
        setBody(
            '<iframe id="dvOfficeFrame" class="dv-office-frame" ' +
            'src="' + gDocsUrl + '" title="Document Preview" ' +
            'onload="window.DocumentViewer._onOfficeLoad()" ' +
            'onerror="window.DocumentViewer._onOfficeError()"></iframe>'
        );
        // Google Docs Viewer doesn't always fire onload reliably, hide loading after 5s fallback
        setTimeout(function () { showLoading(false); }, 5000);
    }

    function renderDownloadFallback(filename, url) {
        setToolbarMode('generic');
        setBody(
            '<div class="dv-download-fallback">' +
            '<i class="ri-file-unknow-line"></i>' +
            '<div><p class="mb-2 text-light">Preview not available for this file type.</p>' +
            '<a href="' + url + '" download class="btn btn-outline-light btn-sm"><i class="ri-download-2-line me-1"></i>Download ' + escapeHtml(filename || 'File') + '</a></div>' +
            '</div>'
        );
        showLoading(false);
    }

    function renderError(message, url, filename) {
        setToolbarMode('generic');
        setBody(
            '<div class="dv-error">' +
            '<i class="ri-error-warning-line"></i>' +
            '<p>' + escapeHtml(message) + '</p>' +
            (url ? '<a href="' + url + '" download class="btn btn-outline-light btn-sm mt-2"><i class="ri-download-2-line me-1"></i>Download</a>' : '') +
            '</div>'
        );
        showLoading(false);
    }

    // ── Image transforms ─────────────────────────────────────────────────────
    function updateImageTransform() {
        const img = $id('dvImg');
        if (img) {
            img.style.transform = 'scale(' + _zoom + ') rotate(' + _rotate + 'deg)';
        }
        const label = $id('dvZoomLevel');
        if (label) label.textContent = Math.round(_zoom * 100) + '%';
    }

    function zoomIn() {
        _zoom = Math.min(_zoom + 0.25, 4);
        updateImageTransform();
    }

    function zoomOut() {
        _zoom = Math.max(_zoom - 0.25, 0.25);
        updateImageTransform();
    }

    function rotateImage() {
        _rotate = (_rotate + 90) % 360;
        updateImageTransform();
    }

    function resetImageTransform() {
        _zoom   = 1;
        _rotate = 0;
        updateImageTransform();
    }

    // ── Fullscreen ───────────────────────────────────────────────────────────
    function toggleFullscreen() {
        const modal = $id('documentViewerModal');
        if (modal) {
            modal.classList.toggle('dv-fullscreen');
            const btn = $id('dvBtnFullscreen');
            if (btn) {
                const isFs = modal.classList.contains('dv-fullscreen');
                btn.innerHTML = '<i class="ri-' + (isFs ? 'fullscreen-exit' : 'fullscreen') + '-line"></i>';
            }
        }
    }

    // ── Print ────────────────────────────────────────────────────────────────
    function printDocument() {
        const frame = $id('dvPdfFrame') || $id('dvOfficeFrame');
        if (frame) {
            try { frame.contentWindow.print(); } catch (e) { window.open(_currentUrl, '_blank'); }
        } else {
            const win = window.open(_currentUrl, '_blank');
            if (win) { win.onload = function () { win.print(); }; }
        }
    }

    // ── Download ─────────────────────────────────────────────────────────────
    function downloadFile() {
        const a = document.createElement('a');
        a.href     = _currentUrl;
        a.download = _currentFilename || 'download';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // ── Utilities ────────────────────────────────────────────────────────────
    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Public API ───────────────────────────────────────────────────────────
    function openDocumentViewer(opts) {
        opts = opts || {};

        _currentUrl      = opts.url      || '';
        _currentFilename = opts.filename || '';
        _currentMime     = opts.mime     || '';
        _currentTitle    = opts.title    || opts.filename || 'Document Viewer';

        if (!_currentUrl) {
            console.warn('[DocumentViewer] No URL provided');
            return;
        }

        const type = detectType(_currentMime, _currentFilename);

        // Reset state
        _zoom   = 1;
        _rotate = 0;

        setTitle(_currentTitle);
        showLoading(true);

        // Open Bootstrap modal
        const modalEl = $id('documentViewerModal');
        if (!modalEl) {
            console.error('[DocumentViewer] Modal element #documentViewerModal not found');
            return;
        }

        // Ensure fullscreen cleared on open
        modalEl.classList.remove('dv-fullscreen');
        const fsBtn = $id('dvBtnFullscreen');
        if (fsBtn) fsBtn.innerHTML = '<i class="ri-fullscreen-line"></i>';

        const bsModal = window.bootstrap && window.bootstrap.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;

        if (bsModal) {
            bsModal.show();
        } else {
            // jQuery fallback
            if (window.$) { $(modalEl).modal('show'); }
        }

        // Render by type
        switch (type) {
            case 'pdf':
                renderPdf(_currentUrl);
                break;
            case 'image':
                renderImage(_currentUrl, _currentFilename);
                showLoading(false);
                break;
            case 'txt':
                renderTxt(_currentUrl);
                break;
            case 'office':
                renderOffice(_currentUrl);
                showLoading(false);
                break;
            default:
                renderDownloadFallback(_currentFilename, _currentUrl);
                break;
        }
    }

    // Callbacks for async events
    function _onImageLoad()   { showLoading(false); }
    function _onImageError()  { renderError('Could not load image.', _currentUrl, _currentFilename); }
    function _onOfficeLoad()  { showLoading(false); }
    function _onOfficeError() { renderDownloadFallback(_currentFilename, _currentUrl); }

    // ── Event bindings ───────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const modal = $id('documentViewerModal');
        if (!modal) return;

        // Clean up on modal close
        modal.addEventListener('hidden.bs.modal', function () {
            setBody('');
            modal.classList.remove('dv-fullscreen');
        });

        // Toolbar buttons
        const bindClick = function (id, fn) {
            const el = $id(id);
            if (el) el.addEventListener('click', fn);
        };

        bindClick('dvBtnZoomIn',    zoomIn);
        bindClick('dvBtnZoomOut',   zoomOut);
        bindClick('dvBtnRotate',    rotateImage);
        bindClick('dvBtnReset',     resetImageTransform);
        bindClick('dvBtnFullscreen', toggleFullscreen);
        bindClick('dvBtnPrint',     printDocument);
        bindClick('dvBtnDownload',  downloadFile);
        bindClick('dvBtnPdfPrint',  printDocument);
        bindClick('dvBtnPdfDownload', downloadFile);
        bindClick('dvBtnImgDownload', downloadFile);
    });

    // ── Export ───────────────────────────────────────────────────────────────
    window.openDocumentViewer = openDocumentViewer;
    window.DocumentViewer = {
        open:           openDocumentViewer,
        zoomIn:         zoomIn,
        zoomOut:        zoomOut,
        rotate:         rotateImage,
        download:       downloadFile,
        print:          printDocument,
        toggleFullscreen: toggleFullscreen,
        _onImageLoad:   _onImageLoad,
        _onImageError:  _onImageError,
        _onOfficeLoad:  _onOfficeLoad,
        _onOfficeError: _onOfficeError,
    };

})(window);
