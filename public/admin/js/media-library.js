/**
 * Resolve media URLs for <img src> and URL validation.
 * Root-relative paths (/storage/...) must be resolved against the app base (subdirectory installs),
 * otherwise the browser requests http://localhost/storage/... instead of http://localhost/news/public/storage/...
 */
function setMediaLibraryModalTitle(modalId, text) {
    const el = document.getElementById(`${modalId}LabelText`);
    if (el) {
        el.textContent = text;
    } else {
        const h = document.getElementById(`${modalId}Label`);
        if (h) {
            h.textContent = text;
        }
    }
}

function setMediaSetFeaturedButtonText(btn, text) {
    if (!btn) {
        return;
    }
    const span = btn.querySelector('.set-featured-text');
    if (span) {
        span.textContent = text;
    } else {
        btn.textContent = text;
    }
}

/** macOS/Windows junk files that must never be uploaded (AppleDouble, folder metadata, etc.). */
function getJunkUploadFileReason(file) {
    const name = file && file.name ? String(file.name) : '';
    const base = name.split(/[/\\]/).pop() || name;
    if (!base) {
        return null;
    }
    if (base.startsWith('._')) {
        return 'macOS metadata file — select the original photo without the ._ prefix';
    }
    if (base === '.DS_Store') {
        return 'macOS folder metadata';
    }
    if (base === 'Thumbs.db' || base === 'desktop.ini') {
        return 'system metadata file';
    }
    if (base.startsWith('.') && base.length > 1) {
        return 'hidden system file';
    }
    return null;
}

/**
 * Admin panel route prefix (e.g. /ppadmlog), not static /admin/js assets.
 */
function getAdminRouteBase() {
    if (typeof window.getAdminRouteBase === 'function') {
        return window.getAdminRouteBase();
    }

    const appBase = (window.appConfig && window.appConfig.baseUrl)
        ? String(window.appConfig.baseUrl).replace(/\/$/, '')
        : String(window.appBaseUrl || window.location.origin).replace(/\/$/, '');
    const adminPath = String(window.adminBasePath || 'ppadmlog').replace(/^\/+|\/+$/g, '');

    return `${appBase}/${adminPath}`;
}

function resolveMediaUrl(urlString) {
    if (!urlString) {
        return '';
    }
    const s = String(urlString).trim();
    if (/^https?:\/\//i.test(s)) {
        return s;
    }
    const base = (typeof window !== 'undefined' && window.appConfig && window.appConfig.baseUrl)
        ? window.appConfig.baseUrl
        : window.location.origin;
    const baseNormalized = base.endsWith('/') ? base : base + '/';
    const path = s.startsWith('/') ? s.slice(1) : s;
    try {
        return new URL(path, baseNormalized).href;
    } catch (e) {
        return s;
    }
}

class MediaLibrary {
    constructor(options = {}) {
        this.modalId = options.modalId || 'mediaLibraryModal';
        /** Shared media library (Spatie media on MediaLibrary singleton). */
        this.apiMode = options.apiMode || 'common';
        this.modal = document.getElementById(this.modalId);
        this._listUrlOverride = this.modal?.dataset?.mediaListUrl || options.listUrl || null;
        this._uploadUrlOverride = this.modal?.dataset?.mediaUploadUrl || options.uploadUrl || null;
        this.mediaItems = document.getElementById(`mediaItems-${this.modalId}`);
        this.dropZone = document.getElementById(`dropZone-${this.modalId}`);
        this.fileInput = document.getElementById(`fileInput-${this.modalId}`);
        this.selectFilesBtn = document.getElementById(`selectFilesBtn-${this.modalId}`);
        this.setFeaturedImageBtn = document.getElementById(`setFeaturedImageBtn-${this.modalId}`);
        this.mediaLoading = document.getElementById(`mediaLoading-${this.modalId}`);
        this.attachmentDetails = document.getElementById(`attachmentDetails-${this.modalId}`);
        this.noAttachmentDetails = document.getElementById(`noAttachmentDetails-${this.modalId}`);

        // Attachment panel preview elements
        this.mediaPreviewWrap     = document.getElementById(`mediaPreviewWrap-${this.modalId}`);
        this.mediaPreviewImg      = document.getElementById(`mediaPreviewImg-${this.modalId}`);
        this.ml360Compact         = document.getElementById(`ml360Compact-${this.modalId}`);
        this.ml360ManageBtn       = document.getElementById(`ml360ManageBtn-${this.modalId}`);
        this.ml360ManageModalEl   = document.getElementById(`ml360ManageModal-${this.modalId}`);
        this.ml360Panel           = document.getElementById(`ml360Panel-${this.modalId}`);
        if (this.ml360ManageModalEl && this.ml360ManageModalEl.parentElement !== document.body) {
            document.body.appendChild(this.ml360ManageModalEl);
        }
        this.mediaPreviewIcon     = document.getElementById(`mediaPreviewIcon-${this.modalId}`);
        this.mediaPreviewIconEl   = document.getElementById(`mediaPreviewIconEl-${this.modalId}`);
        this.mediaPreviewIconType = document.getElementById(`mediaPreviewIconType-${this.modalId}`);
        this.panoramaViewerEl     = null;
        this.panoramaViewer       = null;
        this.ml360ManageModal     = null;
        this.current360MediaId    = null;
        this._current360Media     = null;
        this._ml360Bound          = false;
        this._360EditMode         = 'init';
        this._360SyncRaf          = null;
        this._360ApplyingViewer   = false;

        // Inline forced-type badge container (sits next to search field)
        this.inlineForcedBadge = document.getElementById(`ml-forced-badge-${this.modalId}`);

        // Filter elements
        this.searchInput = document.getElementById(`mediaLibrarySearch-${this.modalId}`);
        this.typeFilter = document.getElementById(`mediaLibraryTypeFilter-${this.modalId}`);
        this.dateFilter = document.getElementById(`mediaLibraryDateFilter-${this.modalId}`);
        this.sortFilter = document.getElementById(`mediaLibrarySortFilter-${this.modalId}`);

        this.selectedItem    = null;
        this.selectedItems   = [];        // current-page DOM elements that are selected
        this.selectedItemIds = new Set(); // persistent IDs across filter/page changes (multiple mode)
        this.targetBox = null;
        this.options = { ...options };
        this.imageSelectedCallback  = options.onImageSelected  || function() {};
        this.imagesSelectedCallback = options.onImagesSelected || null;
        this.allowedTypes = options.allowedTypes || ['image/*'];
        this.multiple = options.multiple || false;
        this.title = options.title || 'Select Media';
        this.setButtonText = options.setButtonText;
        this.mediaItemsLoaded = false;
        this.mediaItemsCache = [];
        /** null | 'image' | 'video' | 'pdf' — locks the type filter when set. */
        this.forcedType = options.forcedType || null;
        /** Match admin media gallery: list every `media` row unless `options.listScope` is `'library'`. */
        this.currentFilters = {
            search: '',
            type: this.forcedType || '',
            date: '',
            sort: 'newest',
            scope: options.listScope === 'library' ? 'library' : 'all',
        };

        this.currentPage = 1;
        this.totalPages  = 1;
        this.totalItems  = 0;
        /** Picker modal: 18 per page (6×3 grid) — must match `MediaLibraryController` default. */
        this.perPage = 18;
        this.allItemsLoaded = false;
        this.isLoading = false;
        this.paginationContainer = document.getElementById(`mediaPagination-${this.modalId}`);

        this.init();
    }

    /** List + filter JSON (paginated items). */
    getListUrl() {
        return this._listUrlOverride || `${getAdminRouteBase()}/media/json`;
    }

    /** Multipart upload endpoint. */
    getUploadUrl() {
        return this._uploadUrlOverride || `${getAdminRouteBase()}/media`;
    }

    /** Detail JSON for sidebar. */
    getDetailUrl(itemId) {
        return `${getAdminRouteBase()}/media/${itemId}`;
    }

    /** PUT metadata (title, alt, etc.). */
    getSaveUrl(itemId) {
        return `${getAdminRouteBase()}/media/${itemId}`;
    }

    init() {
        // Initialize Bootstrap modal
        this.modalInstance = new bootstrap.Modal(this.modal);

        if (this.setButtonText === undefined || this.setButtonText === null) {
            this.setButtonText = this.multiple ? 'Select Files' : 'Select Image';
        }
        setMediaLibraryModalTitle(this.modalId, this.title);
        setMediaSetFeaturedButtonText(this.setFeaturedImageBtn, this.setButtonText);

        // Event listeners for filters
        this.searchInput.addEventListener('input', debounce(() => {
            this.currentFilters.search = this.searchInput.value;
            this.currentPage = 1;
            this.mediaItemsLoaded = false;
            this.loadMediaItems();
        }, 300));

        this.typeFilter.addEventListener('change', () => {
            if (this.forcedType) return; // locked — ignore manual changes
            this.currentFilters.type = this.typeFilter.value;
            this.currentPage = 1;
            this.mediaItemsLoaded = false;
            this.loadMediaItems();
        });

        this.dateFilter.addEventListener('change', () => {
            this.currentFilters.date = this.dateFilter.value;
            this.currentPage = 1;
            this.mediaItemsLoaded = false;
            this.loadMediaItems();
        });

        this.sortFilter.addEventListener('change', () => {
            this.currentFilters.sort = this.sortFilter.value;
            this.currentPage = 1;
            this.mediaItemsLoaded = false;
            this.loadMediaItems();
        });

        // Event listeners
        this.selectFilesBtn.addEventListener('click', (e) => {
            this.fileInput.click();
        });
        this.fileInput.addEventListener('change', (e) => {
            this.handleFileSelect(e);
        });
        this.setFeaturedImageBtn.addEventListener('click', () => this.handleSetFeaturedImage());

        // Drag and drop
        this.dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.dropZone.classList.add('drag-over');
        });

        this.dropZone.addEventListener('dragleave', () => {
            this.dropZone.classList.remove('drag-over');
        });

        this.dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            this.dropZone.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            this.uploadFiles(files);
        });

        // Load media items when tab is shown
        this.modal.addEventListener('shown.bs.modal', () => {
            if (!this.mediaItemsLoaded) {
                this.loadMediaItems();
            }
        });

        // Clear ALL visual selection when modal is hidden (covers both modes)
        this.modal.addEventListener('hide.bs.modal', () => {
            if (this.ml360ManageModalEl?.classList.contains('show')) {
                this._close360ManageModal();
            }
        });
        this.modal.addEventListener('hidden.bs.modal', () => {
            // Strip selected class from every rendered grid item
            this.mediaItems.querySelectorAll('.media-item.selected')
                .forEach(el => el.classList.remove('selected'));
            this.clearSelection();
        });

        let copyBtn = document.getElementById('copyUrlBtn');
        copyBtn.onclick =  function() {
            const urlInput = document.getElementById('mediaUrl');
            urlInput.select();
            document.execCommand('copy');
            showToast('URL copied to clipboard', 'success');
        }

        // Apply any forced type filter set by the caller
        this.applyForcedType();
        this._bind360PanelControlsOnce();
    }

    setImageSelectedCallback(callback) {
        this.imageSelectedCallback = callback;
    }

    setImagesSelectedCallback(callback) {
        this.imagesSelectedCallback = callback;
    }

    /**
     * Refresh the confirm button text and enabled state in multiple mode.
     * Uses selectedItemIds.size so the count stays correct across filter changes
     * (selectedItems only tracks items visible in the current grid).
     */
    _updateMultiSelectUI() {
        const count = this.selectedItemIds.size;
        this.setFeaturedImageBtn.disabled = count === 0;
        const base = (this.setButtonText || 'Add').replace(/\s*\(\d+\)$/, '');
        setMediaSetFeaturedButtonText(
            this.setFeaturedImageBtn,
            count > 0 ? `${base} (${count})` : base
        );
    }

    /**
     * Lock (or unlock) the type filter dropdown and the upload file-input accept attribute.
     * Call this whenever forcedType changes.
     *
     * The dropdown option values are full MIME types (e.g. 'image/jpeg'), so we cannot
     * set typeFilter.value to the short group name ('image').  Instead we:
     *   - reset the dropdown to "All Types" (value='') and disable it
     *   - show/hide a small badge label next to it
     *   - let loadMediaItems() send type=<forcedType> which the backend handles as a prefix match
     */
    applyForcedType() {
        const acceptMap = { image: 'image/*', video: 'video/*', pdf: 'application/pdf' };
        const labelMap  = { image: 'Images only', video: 'Videos only', pdf: 'PDFs only' };
        const badges = [];

        if (this.typeFilter) {
            if (this.forcedType) {
                this.currentFilters.type  = this.forcedType;
                this.typeFilter.value     = '';
                this.typeFilter.disabled  = true;
                this.typeFilter.style.opacity = '0.5';
                badges.push(labelMap[this.forcedType] || this.forcedType);
                if (this.fileInput) this.fileInput.accept = acceptMap[this.forcedType] || '*/*';
            } else {
                this.typeFilter.disabled      = false;
                this.typeFilter.style.opacity = '';
                this.currentFilters.type      = this.typeFilter.value;
                if (this.fileInput) this.fileInput.accept = '';
            }
        }

        if (this.options && this._shouldFilter360List()) {
            badges.push('360° photos only');
        }
        if (this.inlineForcedBadge) {
            this.inlineForcedBadge.innerHTML = badges.length
                ? badges.map(label => `
                    <span class="ml-inline-forced-badge">
                        <i class="ri-filter-3-line" style="font-size:.7rem;"></i>
                        ${label}
                    </span>`).join('')
                : '';
        }
    }

    _shouldFilter360List() {
        if (!this.options) return false;
        return !!(this.options.picker360 || this.options.filter_is_360);
    }

    _shouldFilterByProperty() {
        if (!this.options) return false;
        return !!(this.options.filter_by_property || this.options.context);
    }

    _getUploadPropertyId() {
        if (!this.options) return null;
        return this.options.upload_property_id || this.options.property_id || null;
    }

    _requires360() {
        return !!(this.options && (this.options.picker360 || this.options.is_360));
    }

    _is360Item(item) {
        return !!(item && (item.is_360 === true || item.is_360 === 1 || item.is_360 === '1'));
    }

    _show360RequiredToast() {
        showToast(
            'Only 360° photos can be selected here. Upload a 2:1 equirectangular image (width at least 2000px) or pick an existing 360° photo from the library.',
            'error'
        );
    }

    _getAdminBase() {
        return getAdminRouteBase();
    }

    _ensurePannellum() {
        if (window.pannellum) {
            return Promise.resolve();
        }
        if (!MediaLibrary._pannellumPromise) {
            MediaLibrary._pannellumPromise = new Promise((resolve, reject) => {
                if (!document.querySelector('link[data-ml-pannellum]')) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css';
                    link.setAttribute('data-ml-pannellum', '1');
                    document.head.appendChild(link);
                }
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js';
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Failed to load 360° viewer'));
                document.head.appendChild(script);
            });
        }
        return MediaLibrary._pannellumPromise;
    }

    _destroyPanoramaViewer() {
        this._stop360ViewerSync();
        if (this.panoramaViewer) {
            try {
                this.panoramaViewer.destroy();
            } catch (e) { /* ignore */ }
            this.panoramaViewer = null;
        }
        if (this.panoramaViewerEl) {
            this.panoramaViewerEl.innerHTML = '';
        }
    }

    _round360(n) {
        return Math.round(parseFloat(n) * 10) / 10;
    }

    _get360SliderEl(name) {
        return document.getElementById(`ml360${name}-${this.modalId}`);
    }

    _get360SliderValue(name, fallback = 0) {
        const el = this._get360SliderEl(name);
        return el ? parseFloat(el.value) : fallback;
    }

    _set360Slider(name, value) {
        const el = this._get360SliderEl(name);
        const valEl = document.getElementById(`ml360${name}Val-${this.modalId}`);
        const v = this._round360(value);
        if (el) el.value = String(v);
        if (valEl) valEl.textContent = String(v);
    }

    _get360ModeValues(mode) {
        if (mode === 'thumb') {
            return {
                yaw: this._get360SliderValue('ThumbYaw', 0),
                pitch: this._get360SliderValue('ThumbPitch', 0),
                hfov: this._get360SliderValue('ThumbHfov', 90),
            };
        }
        return {
            yaw: this._get360SliderValue('InitYaw', 0),
            pitch: this._get360SliderValue('InitPitch', 0),
            hfov: this._get360SliderValue('InitHfov', 100),
        };
    }

    _set360ModeValues(mode, yaw, pitch, hfov) {
        if (mode === 'thumb') {
            this._set360Slider('ThumbYaw', yaw);
            this._set360Slider('ThumbPitch', pitch);
            this._set360Slider('ThumbHfov', hfov);
            return;
        }
        this._set360Slider('InitYaw', yaw);
        this._set360Slider('InitPitch', pitch);
        this._set360Slider('InitHfov', hfov);
    }

    _isThumbLinkedToInit() {
        return document.getElementById(`ml360ThumbMatchInit-${this.modalId}`)?.checked === true;
    }

    _copyInitAnglesToThumb(updateViewer = true) {
        const init = this._get360ModeValues('init');
        this._set360ModeValues('thumb', init.yaw, init.pitch, init.hfov);
        if (updateViewer && this._360EditMode === 'thumb') {
            this._applyViewerFrom360Mode('thumb');
        }
    }

    _syncThumbMatchInitUI() {
        const linked = this._isThumbLinkedToInit();
        const sec = document.getElementById(`ml360SectionThumb-${this.modalId}`);
        if (sec) sec.classList.toggle('is-linked', linked);
        ['ThumbYaw', 'ThumbPitch', 'ThumbHfov'].forEach((name) => {
            const el = this._get360SliderEl(name);
            if (el) el.disabled = linked;
        });
        if (linked) {
            this._copyInitAnglesToThumb(false);
        }
    }

    _detectThumbMatchInit() {
        const init = this._get360ModeValues('init');
        const thumb = this._get360ModeValues('thumb');
        const match = init.yaw === thumb.yaw && init.pitch === thumb.pitch && init.hfov === thumb.hfov;
        const cb = document.getElementById(`ml360ThumbMatchInit-${this.modalId}`);
        if (cb) cb.checked = match;
        this._syncThumbMatchInitUI();
    }

    _set360ThumbPreviewLoading(loading) {
        const loadingEl = document.getElementById(`ml360ThumbPreviewLoading-${this.modalId}`);
        const img = document.getElementById(`ml360ThumbPreviewImg-${this.modalId}`);
        const empty = document.getElementById(`ml360ThumbPreviewEmpty-${this.modalId}`);
        if (loading) {
            if (loadingEl) loadingEl.classList.remove('d-none');
            if (img) img.classList.add('d-none');
            if (empty) empty.classList.add('d-none');
        } else if (loadingEl) {
            loadingEl.classList.add('d-none');
        }
    }

    _showGeneratedThumbPreview(url) {
        const img = document.getElementById(`ml360ThumbPreviewImg-${this.modalId}`);
        const empty = document.getElementById(`ml360ThumbPreviewEmpty-${this.modalId}`);
        this._set360ThumbPreviewLoading(false);
        if (empty) empty.classList.add('d-none');
        if (img && url) {
            img.classList.remove('d-none');
            const resolved = resolveMediaUrl(url);
            img.src = resolved + (resolved.includes('?') ? '&' : '?') + 't=' + Date.now();
        }
    }

    _set360EditMode(mode) {
        this._360EditMode = mode === 'thumb' ? 'thumb' : 'init';
        const thumbSec = document.getElementById(`ml360SectionThumb-${this.modalId}`);
        const initSec = document.getElementById(`ml360SectionInit-${this.modalId}`);
        if (thumbSec) thumbSec.classList.toggle('is-active', this._360EditMode === 'thumb');
        if (initSec) initSec.classList.toggle('is-active', this._360EditMode === 'init');
        this._applyViewerFrom360Mode(this._360EditMode);
    }

    _applyViewerFrom360Mode(mode) {
        if (!this.panoramaViewer) return;
        const { yaw, pitch, hfov } = this._get360ModeValues(mode);
        this._360ApplyingViewer = true;
        this.panoramaViewer.setYaw(yaw);
        this.panoramaViewer.setPitch(pitch);
        this.panoramaViewer.setHfov(hfov);
        this._360ApplyingViewer = false;
    }

    _getViewerOrientation() {
        if (!this.panoramaViewer) {
            return { yaw: 0, pitch: 0, hfov: 100 };
        }
        return {
            yaw: this._round360(this.panoramaViewer.getYaw()),
            pitch: this._round360(this.panoramaViewer.getPitch()),
            hfov: this._round360(this.panoramaViewer.getHfov()),
        };
    }

    _syncActive360ModeFromViewer() {
        if (!this.panoramaViewer || this._360ApplyingViewer) return;
        const { yaw, pitch, hfov } = this._getViewerOrientation();
        this._set360ModeValues(this._360EditMode, yaw, pitch, hfov);
    }

    _start360ViewerSync() {
        if (this._360SyncRaf) return;
        const loop = () => {
            this._syncActive360ModeFromViewer();
            this._360SyncRaf = requestAnimationFrame(loop);
        };
        this._360SyncRaf = requestAnimationFrame(loop);
    }

    _stop360ViewerSync() {
        if (this._360SyncRaf) {
            cancelAnimationFrame(this._360SyncRaf);
            this._360SyncRaf = null;
        }
    }

    _bind360ViewerDragSync() {
        if (!this.panoramaViewer) return;
        const start = () => this._start360ViewerSync();
        const stop = () => {
            this._stop360ViewerSync();
            this._syncActive360ModeFromViewer();
        };
        this.panoramaViewer.on('mousedown', start);
        this.panoramaViewer.on('mouseup', stop);
        this.panoramaViewer.on('touchstart', start);
        this.panoramaViewer.on('touchend', stop);
        this.panoramaViewer.on('zoomchange', () => this._syncActive360ModeFromViewer());
    }

    async _renderPanoramaViewer(url, yaw, pitch, hfov) {
        if (!this.panoramaViewerEl || !url) return;
        try {
            await this._ensurePannellum();
        } catch (e) {
            console.error(e);
            return;
        }
        this._destroyPanoramaViewer();
        this.panoramaViewerEl.classList.remove('d-none');
        this.panoramaViewer = window.pannellum.viewer(this.panoramaViewerEl, {
            type: 'equirectangular',
            panorama: url,
            autoLoad: true,
            yaw: parseFloat(yaw) || 0,
            pitch: parseFloat(pitch) || 0,
            hfov: parseFloat(hfov) || 100,
            minHfov: 50,
            maxHfov: 120,
            showControls: true,
            compass: false,
            keyboardZoom: false,
        });
        this._bind360ViewerDragSync();
    }

    _populate360Panel(media) {
        this.current360MediaId = media.id;
        this._set360Slider('ThumbYaw', media.panorama_thumbnail_yaw ?? 0);
        this._set360Slider('ThumbPitch', media.panorama_thumbnail_pitch ?? 0);
        this._set360Slider('ThumbHfov', media.panorama_thumbnail_hfov ?? 90);
        this._set360Slider('InitYaw', media.panorama_initial_yaw ?? 0);
        this._set360Slider('InitPitch', media.panorama_initial_pitch ?? 0);
        this._set360Slider('InitHfov', media.panorama_initial_hfov ?? 100);

        this._set360ThumbPreviewLoading(false);
        if (media.panorama_thumb_path && media.panorama_thumb_url) {
            this._showGeneratedThumbPreview(media.panorama_thumb_url);
        } else {
            const img = document.getElementById(`ml360ThumbPreviewImg-${this.modalId}`);
            const empty = document.getElementById(`ml360ThumbPreviewEmpty-${this.modalId}`);
            if (img) img.classList.add('d-none');
            if (empty) empty.classList.remove('d-none');
        }
        this._detectThumbMatchInit();
    }

    _show360SidebarPreview(media) {
        if (!this.mediaPreviewImg) return;
        const thumbUrl = media.panorama_thumb_path && media.panorama_thumb_url
            ? media.panorama_thumb_url
            : resolveMediaUrl(media.url);
        this.mediaPreviewImg.classList.remove('d-none');
        this.mediaPreviewImg.classList.add('ml-360-sidebar-preview');
        this.mediaPreviewImg.src = thumbUrl + (String(thumbUrl).includes('?') ? '&' : '?') + 't=' + Date.now();
        this.mediaPreviewImg.alt = media.title || '360° photo';
        if (this.mediaPreviewIcon) this.mediaPreviewIcon.classList.add('d-none');
    }

    _update360CompactSummary(media) {
        const summary = document.getElementById(`ml360CompactSummary-${this.modalId}`);
        const thumbImg = document.getElementById(`ml360CompactThumb-${this.modalId}`);
        const iy = this._round360(media.panorama_initial_yaw ?? 0);
        const ip = this._round360(media.panorama_initial_pitch ?? 0);
        const ty = this._round360(media.panorama_thumbnail_yaw ?? 0);
        const tp = this._round360(media.panorama_thumbnail_pitch ?? 0);
        if (summary) {
            summary.textContent = `Visitor ${iy}° / ${ip}° · Thumb ${ty}° / ${tp}°`;
        }
        if (thumbImg) {
            const url = media.panorama_thumb_path && media.panorama_thumb_url
                ? media.panorama_thumb_url
                : resolveMediaUrl(media.url);
            thumbImg.src = url + (String(url).includes('?') ? '&' : '?') + 't=' + Date.now();
        }
    }

    _open360ManageModal() {
        if (!this._current360Media) return;
        this._populate360Panel(this._current360Media);
        this._set360EditMode('init');
        if (!this.ml360ManageModal && this.ml360ManageModalEl && window.bootstrap?.Modal) {
            this.ml360ManageModal = window.bootstrap.Modal.getOrCreateInstance(this.ml360ManageModalEl);
        }
        this.ml360ManageModal?.show();
    }

    _sync360NestedModalLayers() {
        if (!this.ml360ManageModalEl) return;
        this.ml360ManageModalEl.style.zIndex = '1070';
        const backdrops = document.querySelectorAll('.modal-backdrop.show');
        if (backdrops.length >= 2) {
            backdrops[backdrops.length - 1].classList.add('ml-360-manage-backdrop');
            backdrops[backdrops.length - 1].style.zIndex = '1065';
            backdrops[backdrops.length - 2].style.zIndex = '1045';
        } else if (backdrops.length === 1) {
            backdrops[0].classList.add('ml-360-manage-backdrop');
            backdrops[0].style.zIndex = '1065';
        }
    }

    _on360ManageModalShow() {
        document.body.classList.add('ml-360-nested-open');
        if (this.modal) this.modal.classList.add('is-behind-360-modal');
        requestAnimationFrame(() => this._sync360NestedModalLayers());
    }

    _on360ManageModalHidden() {
        document.body.classList.remove('ml-360-nested-open');
        if (this.modal) this.modal.classList.remove('is-behind-360-modal');
        document.querySelectorAll('.modal-backdrop.ml-360-manage-backdrop').forEach((el) => {
            el.classList.remove('ml-360-manage-backdrop');
            el.style.zIndex = '';
        });
    }

    _close360ManageModal() {
        if (this.ml360ManageModal) {
            this.ml360ManageModal.hide();
        }
    }

    _show360AttachmentUI(media) {
        this._current360Media = { ...this._current360Media, ...media };
        this.current360MediaId = media.id;
        if (this.ml360Compact) this.ml360Compact.classList.remove('d-none');
        this._update360CompactSummary(this._current360Media);
        this._show360SidebarPreview(this._current360Media);
    }

    _hide360AttachmentUI() {
        if (this.ml360Compact) this.ml360Compact.classList.add('d-none');
        this._close360ManageModal();
        this._destroyPanoramaViewer();
        this.current360MediaId = null;
        this._current360Media = null;
        if (this.mediaPreviewImg) this.mediaPreviewImg.classList.remove('ml-360-sidebar-preview');
    }

    _sync360Cache(mediaId, patch) {
        const idx = this.mediaItemsCache.findIndex(
            i => String(i.id) === String(mediaId) || String(i.ID) === String(mediaId)
        );
        if (idx >= 0) {
            Object.assign(this.mediaItemsCache[idx], patch);
        }
    }

    _bind360PanelControlsOnce() {
        if (this._ml360Bound) return;
        this._ml360Bound = true;
        const mid = this.modalId;
        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

        const activateSection = (mode) => {
            this._set360EditMode(mode);
        };

        this.ml360ManageBtn?.addEventListener('click', () => this._open360ManageModal());
        this.ml360ManageModalEl?.addEventListener('show.bs.modal', () => this._on360ManageModalShow());
        this.ml360ManageModalEl?.addEventListener('shown.bs.modal', () => {
            this._sync360NestedModalLayers();
            if (!this._current360Media) return;
            this.panoramaViewerEl = document.getElementById(`ml360ModalPanoramaViewer-${mid}`);
            const init = this._get360ModeValues('init');
            void this._renderPanoramaViewer(
                resolveMediaUrl(this._current360Media.url),
                init.yaw,
                init.pitch,
                init.hfov
            );
        });
        this.ml360ManageModalEl?.addEventListener('hidden.bs.modal', () => {
            this._on360ManageModalHidden();
            this._destroyPanoramaViewer();
            this.panoramaViewerEl = null;
        });

        document.getElementById(`ml360SectionThumb-${mid}`)?.addEventListener('click', (e) => {
            if (e.target.closest('.ml360-slider') || e.target.closest('.form-check')) return;
            activateSection('thumb');
        });
        document.getElementById(`ml360SectionInit-${mid}`)?.addEventListener('click', (e) => {
            if (e.target.closest('.ml360-slider')) return;
            activateSection('init');
        });

        document.getElementById(`ml360ThumbMatchInit-${mid}`)?.addEventListener('change', () => {
            this._syncThumbMatchInitUI();
        });

        ['ThumbYaw', 'ThumbPitch', 'ThumbHfov', 'InitYaw', 'InitPitch', 'InitHfov'].forEach((name) => {
            const el = document.getElementById(`ml360${name}-${mid}`);
            if (!el) return;
            el.addEventListener('input', () => {
                const valEl = document.getElementById(`ml360${name}Val-${mid}`);
                if (valEl) valEl.textContent = el.value;
                const mode = name.startsWith('Thumb') ? 'thumb' : 'init';
                if (name.startsWith('Init') && this._isThumbLinkedToInit()) {
                    this._copyInitAnglesToThumb(false);
                }
                if (this._360EditMode === mode) {
                    this._applyViewerFrom360Mode(mode);
                }
            });
            el.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                const mode = name.startsWith('Thumb') ? 'thumb' : 'init';
                if (this._360EditMode !== mode) {
                    this._set360EditMode(mode);
                }
            });
            el.addEventListener('click', (e) => e.stopPropagation());
        });

        document.getElementById(`ml360PreviewBtn-${mid}`)?.addEventListener('click', async () => {
            if (!this.current360MediaId) return;
            const btn = document.getElementById(`ml360PreviewBtn-${mid}`);
            if (btn) btn.disabled = true;
            try {
                const response = await fetch(`${this._getAdminBase()}/media/${this.current360MediaId}/360-preview`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        yaw: this._get360SliderValue('ThumbYaw', 0),
                        pitch: this._get360SliderValue('ThumbPitch', 0),
                        hfov: this._get360SliderValue('ThumbHfov', 90),
                    }),
                });
                const res = await response.json();
                if (res.success && res.data?.preview_url) {
                    this._showGeneratedThumbPreview(res.data.preview_url);
                    showToast('Thumbnail preview generated', 'success');
                } else {
                    showToast(res.message || 'Could not generate preview', 'error');
                }
            } catch (e) {
                showToast('Could not generate preview', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        });

        document.getElementById(`ml360SaveBtn-${mid}`)?.addEventListener('click', async () => {
            if (!this.current360MediaId) return;
            const btn = document.getElementById(`ml360SaveBtn-${mid}`);
            if (btn) btn.disabled = true;
            try {
                if (this._isThumbLinkedToInit()) {
                    this._copyInitAnglesToThumb(false);
                }
                this._set360ThumbPreviewLoading(true);
                const payload = {
                    is_360: true,
                    panorama_thumbnail_yaw: this._get360SliderValue('ThumbYaw', 0),
                    panorama_thumbnail_pitch: this._get360SliderValue('ThumbPitch', 0),
                    panorama_thumbnail_hfov: this._get360SliderValue('ThumbHfov', 90),
                    panorama_initial_yaw: this._get360SliderValue('InitYaw', 0),
                    panorama_initial_pitch: this._get360SliderValue('InitPitch', 0),
                    panorama_initial_hfov: this._get360SliderValue('InitHfov', 100),
                };
                const response = await fetch(`${this._getAdminBase()}/media/${this.current360MediaId}/360-angles`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const res = await response.json();
                if (res.success) {
                    showToast(res.message || '360° settings saved', 'success');
                    if (res.data) {
                        this._sync360Cache(this.current360MediaId, {
                            is_360: true,
                            panorama_thumbnail_yaw: res.data.panorama_thumbnail_yaw,
                            panorama_thumbnail_pitch: res.data.panorama_thumbnail_pitch,
                            panorama_thumbnail_hfov: res.data.panorama_thumbnail_hfov,
                            panorama_initial_yaw: res.data.panorama_initial_yaw,
                            panorama_initial_pitch: res.data.panorama_initial_pitch,
                            panorama_initial_hfov: res.data.panorama_initial_hfov,
                            panorama_thumb_url: res.data.panorama_thumb_url,
                            panorama_thumb_path: res.data.panorama_thumb_path,
                        });
                        if (this._current360Media) {
                            Object.assign(this._current360Media, res.data);
                            this._update360CompactSummary(this._current360Media);
                            this._show360SidebarPreview(this._current360Media);
                        }
                        if (res.data.panorama_thumb_url) {
                            this._showGeneratedThumbPreview(res.data.panorama_thumb_url);
                        } else {
                            this._set360ThumbPreviewLoading(false);
                        }
                    }
                } else {
                    this._set360ThumbPreviewLoading(false);
                    showToast(res.message || 'Could not save 360° settings', 'error');
                }
            } catch (e) {
                this._set360ThumbPreviewLoading(false);
                showToast('Could not save 360° settings', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }

    /** Returns true if the given media-item data-type attribute matches the forced type. */
    _isTypeAllowed(dataType) {
        if (!this.forcedType) return true;
        return dataType === this.forcedType;
    }

    /**
     * Load (or append) media items.
     * @param {boolean} append  true = Load More (keep existing grid); false = fresh load (clear grid first)
     */
    async loadMediaItems(append = false) {
        if (this.isLoading) return;

        this.isLoading = true;

        if (!append) {
            // Fresh load: clear grid so spinner is visible over an empty area.
            // Also discard stale DOM refs — addMediaItem will rebuild selectedItems
            // from selectedItemIds when it renders each card.
            this.mediaItems.innerHTML = '';
            this.selectedItems  = [];
            this.allItemsLoaded = false;
        }

        this.mediaLoading.style.display = 'block';

        try {
            const queryParams = new URLSearchParams();
            for (const [key, value] of Object.entries(this.currentFilters)) {
                if (value && key !== 'scope') queryParams.append(key, value);
            }
            queryParams.set('scope', this.currentFilters.scope || 'all');
            if (this.forcedType) queryParams.set('type', this.forcedType);
            queryParams.append('page', this.currentPage);
            queryParams.append('per_page', this.perPage);
            if (this._shouldFilterByProperty() && this.options && this.options.property_id) {
                queryParams.set('property_id', this.options.property_id);
            }
            if (this.options && this.options.context)     queryParams.set('context', this.options.context);
            if (this._shouldFilter360List())              queryParams.set('is_360', '1');

            const response = await fetch(`${this.getListUrl()}?${queryParams.toString()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                credentials: 'same-origin'
            });

            const result = await response.json();
            this.mediaLoading.style.display = 'none';
            this.isLoading = false;

            if (result.data) {
                this.totalPages  = result.data.last_page;
                this.totalItems  = result.data.total;
                this.perPage     = result.data.per_page;
                this.currentPage = result.data.current_page;
            }

            const rawItems = result.data?.items || [];
            const validItems = rawItems.filter(item => {
                try {
                    if (!item.url) return false;
                    const url = new URL(resolveMediaUrl(item.url));
                    return url.protocol === 'http:' || url.protocol === 'https:';
                } catch (e) { return false; }
            });

            if (append) {
                this.mediaItemsCache = [...this.mediaItemsCache, ...validItems];
            } else {
                this.mediaItemsCache = validItems;
            }

            this.displayMediaItems(validItems, append);
            this.mediaItemsLoaded = true;
            this.allItemsLoaded   = (this.currentPage >= this.totalPages);
            this.updatePagination();

        } catch (error) {
            console.error('Failed to load media items:', error);
            this.mediaLoading.style.display = 'none';
            this.isLoading = false;
            if (!append) {
                this.mediaItems.innerHTML = '<div class="text-center text-danger p-4">Failed to load media items</div>';
            }
        }
    }

    /**
     * Render a "Load More" button (if more pages exist) plus an item-count hint.
     * Replaces the old numbered pagination entirely.
     */
    updatePagination() {
        if (!this.paginationContainer) return;

        const total   = this.totalItems || 0;
        const loaded  = this.mediaItemsCache.length;
        const hasMore = !this.allItemsLoaded && this.currentPage < this.totalPages;

        let html = '';

        if (hasMore) {
            // More to load: show count + Load More button
            const remaining = total - loaded;
            html += `<p class="media-items-count-hint mb-2">Showing ${loaded} of ${total} item${total !== 1 ? 's' : ''}</p>`;
            html += `
                <button type="button" class="btn btn-primary btn-sm px-4 media-load-more-btn">
                    <i class="ri-arrow-down-line me-1"></i>Load more
                    <span class="ms-1 small opacity-75">(${remaining} remaining)</span>
                </button>`;
        } else if (total > 0) {
            // All loaded: single clean message
            const allMsg = loaded < total
                ? `Showing ${loaded} of ${total} items`
                : `Showing all ${total} item${total !== 1 ? 's' : ''}`;
            html += `<p class="media-items-count-hint mb-0">${allMsg}</p>`;
        }

        this.paginationContainer.innerHTML = html;

        if (hasMore) {
            const btn = this.paginationContainer.querySelector('.media-load-more-btn');
            if (btn) {
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span>Loading…`;
                    this.currentPage++;
                    await this.loadMediaItems(true); // append mode
                });
            }
        }
    }

    /**
     * Render media items.
     * @param {Array}   items   New items to render
     * @param {boolean} append  If true, append to existing grid; if false, replace
     */
    displayMediaItems(items, append = false) {
        if (this._shouldFilter360List()) {
            items = items.filter(item => this._is360Item(item));
        }

        if (!append) {
            this.mediaItems.innerHTML = '';
        }

        if (!append && items.length === 0) {
            const emptyMsg = this._shouldFilter360List()
                ? 'No 360° photos found. Upload a panorama using the Upload files tab, or mark existing photos as 360° in Media Library.'
                : 'No media items found';
            this.mediaItems.innerHTML = `<div class="text-center text-muted p-4 col-span-all" style="grid-column:1/-1">${emptyMsg}</div>`;
            return;
        }

        items.forEach(item => this.addMediaItem(item));
    }

    addMediaItem(item) {
        try {
            if (!item.url) return;
            const abs = resolveMediaUrl(item.url);
            const url = new URL(abs);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') return;
        } catch (e) {
            return;
        }

        const displayUrl = resolveMediaUrl(
            (item.is_360 && item.panorama_thumb_url) ? item.panorama_thumb_url : item.url
        );

        // Use item.id or item.ID for compatibility
        const mediaId = item.id || item.ID;

        const mediaItem = document.createElement('div');
        mediaItem.className = 'media-item';
        mediaItem.dataset.id = mediaId;
        
        // Add data-type attribute based on mime type
        const mime = item.mime_type || '';
        if (mime.startsWith('image/')) {
            mediaItem.dataset.type = 'image';
        } else if (mime.startsWith('video/')) {
            mediaItem.dataset.type = 'video';
        } else if (mime === 'application/pdf') {
            mediaItem.dataset.type = 'pdf';
        } else if (mime.includes('zip') || mime.includes('rar') || mime.includes('7z')) {
            mediaItem.dataset.type = 'archive';
        } else {
            mediaItem.dataset.type = 'file';
        }
        
        const imageTitle = item.title || item.filename || 'Untitled';
        const fileType = mime.split('/')[1] ? mime.split('/')[1].toUpperCase() : 'FILE';
        const placeholderDataUrl = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjxwYXRoIGQ9Ik03NSA2NiBMOTAgODEgTDc1IDk2IiBzdHJva2U9IiNkZWUyZTYiIGZpbGw9Im5vbmUiIHN0cm9rZS13aWR0aD0iMiIvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM2Yzc1N2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';

        let previewContent = '';
        
        if (mime.startsWith('image/')) {
            const badge360 = item.is_360
                ? '<span class="badge bg-dark position-absolute top-0 end-0 m-1" style="font-size:.65rem;z-index:2;">360°</span>'
                : '';
            previewContent = `
                <div class="media-item-preview position-relative">
                    ${badge360}
                    <img src="${displayUrl}" alt="${imageTitle}" onerror="this.onerror=null; this.src='${placeholderDataUrl}'; this.classList.add('placeholder-img');">
                </div>`;
        } else if (mime.startsWith('video/')) {
            previewContent = `
                <div class="media-item-preview">
                    <video src="${displayUrl}" class="media-preview-video" controls></video>
                    <div class="media-type-overlay">
                        <i class="ri-video-line"></i>
                        <span>Video</span>
                    </div>
                </div>`;
        } else if (mime === 'application/pdf') {
            // previewContent = `
            //     <div class="media-item-preview file-preview pdf-preview">
            //         <div class="pdf-document-preview">
            //             <div class="pdf-icon-container">
            //                 <i class="bi bi-file-earmark-pdf pdf-icon"></i>
            //             </div>
            //             <div class="pdf-info">
            //                 <div class="pdf-type">${fileType}</div>
            //                 <div class="pdf-title">${imageTitle}</div>
            //             </div>
                        
            //         </div>
            //     </div>`;

            previewContent = `
                <div class="media-item-preview file-preview">
                    <div class="pdf-icon pdf">
                        <i class="ri-file-pdf-line pdf-icon"></i>
                        <div class="file-info-label">${fileType}</div>
                        <div class="file-info-type" title="${imageTitle}">${imageTitle}</div>
                    </div>
                </div>`;
        } else if (mime.includes('zip') || mime.includes('rar') || mime.includes('7z')) {
            previewContent = `
                <div class="media-item-preview file-preview">
                    <div class="file-icon archive">
                        <i class="ri-file-zip-line"></i>
                        <div class="file-info-label">${fileType}</div>
                        <div class="file-info-type" title="${imageTitle}">${imageTitle}</div>
                    </div>
                </div>`;
        } else {
            previewContent = `
                <div class="media-item-preview file-preview">
                    <div class="file-icon">
                        <i class="ri-file-line"></i>
                        <div class="file-info-label">${fileType}</div>
                        <div class="file-info-type" title="${imageTitle}">${imageTitle}</div>
                    </div>
                </div>`;
        }
        
        // Hover overlay — filename + compact type badge
        const typeLabel = mime.startsWith('image/') ? 'IMG'
                        : mime.startsWith('video/') ? 'VID'
                        : mime === 'application/pdf' ? 'PDF'
                        : mime.startsWith('audio/') ? 'AUD'
                        : (mime.split('/')[1] || 'FILE').toUpperCase().substring(0, 4);
        const displayName = item.file_name || item.title || '';

        mediaItem.innerHTML = `
            ${previewContent}
            <div class="media-item-hover-overlay">
                <span class="overlay-filename" title="${displayName}">${displayName}</span>
                <span class="overlay-type-badge">${typeLabel}</span>
            </div>
            <div class="check"><i class="ri-check-line"></i></div>
        `;

        // Add specific classes based on file type
        if (!mime.startsWith('image/')) {
            mediaItem.classList.add('non-image-item');
        }

        // ── Restore visual selection state if this item was selected before a filter change ──
        if (this.multiple && this.selectedItemIds.has(String(mediaId))) {
            mediaItem.classList.add('selected');
            this.selectedItems.push(mediaItem);
        }

        mediaItem.addEventListener('click', () => {
            if (this.multiple) {
                // ── Multi-select: toggle checkmark + keep selectedItemIds in sync ──
                if (mediaItem.classList.contains('selected')) {
                    // Deselecting
                    mediaItem.classList.remove('selected');
                    this.selectedItemIds.delete(String(mediaId));
                    this.selectedItems = this.selectedItems.filter(el => el !== mediaItem);

                    // Show last remaining selected item's details, or clear panel
                    if (this.selectedItemIds.size > 0) {
                        const lastEl  = this.selectedItems[this.selectedItems.length - 1];
                        // lastEl may be undefined if the last selection is on another page — look up by ID
                        const lastId  = lastEl ? lastEl.dataset.id
                            : Array.from(this.selectedItemIds)[this.selectedItemIds.size - 1];
                        const lastDat = this.mediaItemsCache.find(
                            i => String(i.id) === String(lastId) || String(i.ID) === String(lastId)
                        );
                        if (lastDat) this.updateAttachmentDetails(lastDat);
                    } else {
                        if (this.attachmentDetails)   this.attachmentDetails.classList.add('d-none');
                        if (this.noAttachmentDetails) this.noAttachmentDetails.classList.remove('d-none');
                        if (this.mediaPreviewWrap)    this.mediaPreviewWrap.classList.add('d-none');
                    }
                } else {
                    // Selecting
                    mediaItem.classList.add('selected');
                    this.selectedItemIds.add(String(mediaId));
                    this.selectedItems.push(mediaItem);
                    this.updateAttachmentDetails(item);
                }
                this._updateMultiSelectUI();
            } else {
                // ── Single-select: existing behaviour ─────────────────────
                if (this.selectedItem) {
                    this.selectedItem.classList.remove('selected');
                }
                mediaItem.classList.add('selected');
                this.selectedItem = mediaItem;
                this.updateAttachmentDetails(item);
                this.setFeaturedImageBtn.disabled = false;
            }
        });

        this.mediaItems.appendChild(mediaItem);
    }

    async updateAttachmentDetails(item) {
        if (!this.attachmentDetails || !this.noAttachmentDetails) return;

        this.attachmentDetails.classList.remove('d-none');
        this.noAttachmentDetails.classList.add('d-none');

        // ── Preview area (top of attachment panel) ─────────────────────────
        const mime = item.mime_type || '';
        const is360Item = this._is360Item(item);
        if (this.mediaPreviewWrap) {
            this.mediaPreviewWrap.classList.remove('d-none');
            if (is360Item) {
                const earlyId = item.ID !== undefined ? item.ID : item.id;
                this._show360AttachmentUI({ ...item, id: earlyId });
            } else if (mime.startsWith('image/')) {
                this._hide360AttachmentUI();
                if (this.mediaPreviewImg) {
                    this.mediaPreviewImg.classList.remove('ml-360-sidebar-preview');
                    this.mediaPreviewImg.classList.remove('d-none');
                    this.mediaPreviewImg.src = resolveMediaUrl(item.url);
                    this.mediaPreviewImg.alt = item.title || '';
                    this.mediaPreviewImg.onerror = () => {
                        this.mediaPreviewImg.src = '';
                        this.mediaPreviewImg.classList.add('d-none');
                        if (this.mediaPreviewIcon) this.mediaPreviewIcon.classList.remove('d-none');
                    };
                }
                if (this.mediaPreviewIcon) this.mediaPreviewIcon.classList.add('d-none');
            } else {
                this._hide360AttachmentUI();
                if (this.mediaPreviewImg) this.mediaPreviewImg.classList.add('d-none');
                if (this.mediaPreviewIcon) {
                    this.mediaPreviewIcon.classList.remove('d-none');
                    const iconClass = mime.startsWith('video/') ? 'ri-video-line text-info'
                        : mime === 'application/pdf'           ? 'ri-file-pdf-line text-danger'
                        : mime.startsWith('audio/')            ? 'ri-music-line text-warning'
                        : 'ri-file-line text-muted';
                    if (this.mediaPreviewIconEl) this.mediaPreviewIconEl.className = `fs-1 ${iconClass}`;
                    if (this.mediaPreviewIconType) {
                        this.mediaPreviewIconType.textContent = (mime.split('/')[1] || mime).toUpperCase();
                    }
                }
            }

            // Wire Document Viewer "View" button for viewable types
            const mlViewBtn = document.getElementById(`mlBtnViewDoc-${this.modalId}`);
            if (mlViewBtn) {
                const viewableMimes = [
                    'application/pdf', 'text/plain',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ];
                const isViewable = mime.startsWith('image/') || viewableMimes.includes(mime);
                mlViewBtn.classList.toggle('d-none', !isViewable);
                mlViewBtn.onclick = function () {
                    if (typeof openDocumentViewer === 'function') {
                        openDocumentViewer({
                            url:      resolveMediaUrl(item.url),
                            filename: item.filename || item.title || '',
                            mime:     mime,
                            title:    item.title || item.filename || 'Preview',
                        });
                    }
                };
            }
        }

        // ── Show "Last selected item" hint in multi-select mode only ──────
        const multiHint = this.attachmentDetails.querySelector('.ml-multiselect-hint');
        if (multiHint) multiHint.classList.toggle('d-none', !this.multiple);

        const container = this.modal;

        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        const fileDate = document.getElementById('fileDate');
        const fileSize = document.getElementById('fileSize');
        const fileDimensions = document.getElementById('fileDimensions');
        const altText = document.getElementById('altText');
        const imageTitle = document.getElementById('imageTitle');
        const imageCaption = document.getElementById('imageCaption');

        if (previewImage) {
            previewImage.src = resolveMediaUrl(item.url);
            previewImage.onerror = function() {
                this.onerror = null;
                this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNmMGYwZjAiLz48dGV4dCB4PSI1MCIgeT0iNTAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
            };
        }
        if (fileName) fileName.textContent = item.filename;
        if (fileDate) fileDate.textContent = new Date(item.date).toLocaleString();
        if (fileSize) fileSize.textContent = item.size;
        if (fileDimensions && item.width && item.height) {
            fileDimensions.textContent = `${item.width} × ${item.height}`;
        }
        if (altText) altText.value = '';
        if (imageTitle) imageTitle.value = item.title;
        if (imageCaption) imageCaption.value = '';

        const itemId = item.ID !== undefined ? item.ID : item.id;

        const detailUrl = this.getDetailUrl(itemId);

        const response = await fetch(detailUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            const media = data.data;
            
            // Update form fields
            container.querySelector('#mediaTitle').value = media.title || '';
            container.querySelector('#mediaAltText').value = media.alt_text || '';
            container.querySelector('#mediaCaption').value = media.caption || '';
            container.querySelector('#mediaDescription').value = media.description || '';
            container.querySelector('#mediaUrl').value = resolveMediaUrl(media.url);
 
            // Update file details
            const uploadedRaw = media.uploaded_at || media.created_at;
            container.querySelector('#mediaDate').textContent = uploadedRaw
                ? new Date(uploadedRaw).toLocaleString()
                : '-';
            container.querySelector('#mediaUploader').textContent = media.uploaded_by != null ? String(media.uploaded_by) : 'Unknown';
            container.querySelector('#mediaFilename').textContent = media.filename || media.file_name || '';
            container.querySelector('#mediaType').textContent = media.mime_type;
            container.querySelector('#mediaSize').textContent = formatFileSize(media.file_size);
            container.querySelector('#mediaDimensions').textContent = media.dimensions || 'Unknown';

            if (media.is_360) {
                this._show360AttachmentUI(media);
            } else {
                this._hide360AttachmentUI();
            }
        } else if (is360Item) {
            this._show360AttachmentUI({
                id: itemId,
                url: item.url,
                is_360: true,
                panorama_thumbnail_yaw: item.panorama_thumbnail_yaw ?? 0,
                panorama_thumbnail_pitch: item.panorama_thumbnail_pitch ?? 0,
                panorama_thumbnail_hfov: item.panorama_thumbnail_hfov ?? 90,
                panorama_initial_yaw: item.panorama_initial_yaw ?? 0,
                panorama_initial_pitch: item.panorama_initial_pitch ?? 0,
                panorama_initial_hfov: item.panorama_initial_hfov ?? 100,
                panorama_thumb_url: item.panorama_thumb_url || null,
                panorama_thumb_path: item.panorama_thumb_path || null,
            });
        } else {
            this._hide360AttachmentUI();
        }

        const saveMediaDetails = container.querySelector('#saveMediaDetails');
        
        if(saveMediaDetails){
            saveMediaDetails.onclick = async () => {
                if (!itemId) return;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const formData = {
                    title: container.querySelector('#mediaTitle').value.trim(),
                    alt_text: container.querySelector('#mediaAltText').value.trim(),
                    caption: container.querySelector('#mediaCaption').value.trim(),
                    description: container.querySelector('#mediaDescription').value.trim()
                };

                try {
                    const response = await fetch(this.getSaveUrl(itemId), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(formData)
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        showToast(data.message || 'Media details updated successfully', 'success');
                    } else {
                        showToast(data.message || 'Failed to update media details', 'error');
                    }
                } catch (error) {
                    console.error('Error updating media details:', error);
                    showToast('Failed to update media details', 'error');
                }
            }
        }
    }

    clearSelection() {
        if (this.multiple) {
            this.selectedItems.forEach(el => el.classList.remove('selected'));
            this.selectedItems   = [];
            this.selectedItemIds.clear();
            this._updateMultiSelectUI();
        } else {
            if (this.selectedItem) {
                this.selectedItem.classList.remove('selected');
                this.selectedItem = null;
            }
            this.setFeaturedImageBtn.disabled = true;
        }
        if (this.attachmentDetails)    this.attachmentDetails.classList.add('d-none');
        if (this.noAttachmentDetails)  this.noAttachmentDetails.classList.remove('d-none');
        if (this.mediaPreviewWrap)     this.mediaPreviewWrap.classList.add('d-none');
        this._hide360AttachmentUI();
    }

    /**
     * Upload multiple files sequentially so each response can update the cache safely
     * (parallel XHRs were clearing/rebuilding the grid and racing each other).
     */
    async uploadFiles(files) {
        const allowedTypes = [
            'image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'image/avif',
            'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon',
            'video/mp4', 'video/avi', 'video/webm', 'video/m4v',
            'audio/mp3',
            'application/json', 'application/xml', 'text/xml', 'application/sql', 'text/sql',
            'application/pdf'
        ];
        const isAllowedSvg = (f) =>
            f.name.toLowerCase().endsWith('.svg') &&
            (!f.type || f.type === 'image/svg+xml');
        const isAllowedIco = (f) =>
            f.name.toLowerCase().endsWith('.ico') &&
            (!f.type || f.type === 'image/x-icon' || f.type === 'image/vnd.microsoft.icon');
        const maxSize = 1 * 1024 * 1024 * 1024; // 1GB
        let errors = [];
        let validFiles = [];
        for (let file of files) {
            const junkReason = getJunkUploadFileReason(file);
            if (junkReason) {
                errors.push(`"${file.name}" skipped (${junkReason}).`);
                continue;
            }
            if (!allowedTypes.includes(file.type) && !isAllowedSvg(file) && !isAllowedIco(file)) {
                errors.push(`"${file.name}" is not an allowed type.`);
                continue;
            }
            if (file.size > maxSize) {
                errors.push(`"${file.name}" exceeds the 1GB size limit.`);
                continue;
            }
            validFiles.push(file);
        }
        if (validFiles.length === 0) {
            this._showUploadAlerts(errors, []);
            return;
        }
        if (errors.length > 0) {
            this._showUploadAlerts([], errors);
        }

        // ── Build queue UI inside the drop zone ───────────────────────────
        const queueEl = this._buildUploadQueue(validFiles);
        this.dropZone.appendChild(queueEl);
        const rows = Array.from(queueEl.querySelectorAll('.upload-queue-item'));

        // ── Upload files sequentially, updating the visible queue ──────────
        const uploadedPayloads = [];
        let uploadedCount = 0;
        for (let i = 0; i < validFiles.length; i++) {
            const file  = validFiles[i];
            const rowEl = rows[i] || null;
            this._setQueueRow(rowEl, 'uploading', 0);
            try {
                const data = await this._xhrUploadOneLibraryFile(file, rowEl);
                if (data && data.success && data.data) {
                    uploadedCount++;
                    this.mediaItemsCache.unshift(data.data);

                    if (data.data.auto_detected_360) {
                        showToast('360° photo detected automatically', 'info');
                    } else if (this._requires360()) {
                        showToast(
                            'Uploaded as a normal photo. Not detected as 360° (needs 2:1 ratio, width at least 2000px).',
                            'info'
                        );
                    }

                    if (!this._requires360() || this._is360Item(data.data)) {
                        uploadedPayloads.push(data.data);
                    }
                    this._setQueueRow(rowEl, 'done', 100);
                } else {
                    this._setQueueRow(rowEl, 'error', 0, 'Upload failed');
                }
            } catch (err) {
                const status = (err && err.message === 'aborted') ? 'aborted' : 'error';
                const errMsg = status === 'error' && err && err.message ? err.message : null;
                this._setQueueRow(rowEl, status, 0, errMsg);
                if (status === 'error') console.error('Upload failed:', err);
            }
        }

        // ── Update queue header to reflect outcome ─────────────────────────
        const hdrSpinner = queueEl.querySelector('.upload-queue-spinner');
        const hdrText    = queueEl.querySelector('.upload-queue-hdr-text');
        if (hdrSpinner) hdrSpinner.remove();
        if (hdrText) {
            const ok = uploadedPayloads.length, total = validFiles.length;
            if (uploadedCount === 0) {
                hdrText.textContent = 'Upload failed';
            } else if (this._requires360() && ok === 0) {
                hdrText.textContent = `${uploadedCount} file${uploadedCount > 1 ? 's' : ''} uploaded — no 360° photos detected`;
            } else if (ok === total) {
                hdrText.textContent = `${ok} file${ok > 1 ? 's' : ''} uploaded — loading library…`;
            } else {
                hdrText.textContent = `${ok} of ${total} uploaded as 360° — loading library…`;
            }
        }

        if (uploadedCount === 0) {
            // Leave queue visible so user can read errors; auto-remove after 4 s
            setTimeout(() => queueEl.remove(), 4000);
            return;
        }

        if (uploadedPayloads.length === 0) {
            setTimeout(() => {
                queueEl.remove();
                this._switchToLibraryTab();
            }, 1200);
            return;
        }

        // ── Rebuild grid and auto-select uploaded items ────────────────────
        this.mediaItems.innerHTML = '';
        const visibleItems = this._shouldFilter360List()
            ? this.mediaItemsCache.filter(item => this._is360Item(item))
            : this.mediaItemsCache;
        this.displayMediaItems(visibleItems);

        if (this.multiple) {
            // Clear previous visual selection
            this.selectedItems.forEach(el => el.classList.remove('selected'));
            this.selectedItems   = [];
            this.selectedItemIds.clear();
            // Select newly uploaded items
            uploadedPayloads.forEach(payload => {
                const id = String(payload.id || payload.ID);
                this.selectedItemIds.add(id);
                const el = this.mediaItems.querySelector(`[data-id="${id}"]`);
                if (el) { el.classList.add('selected'); this.selectedItems.push(el); }
            });
            this._updateMultiSelectUI();
        } else {
            const last = uploadedPayloads[uploadedPayloads.length - 1];
            if (this.selectedItem) this.selectedItem.classList.remove('selected');
            const newItem = this.mediaItems.querySelector(`[data-id="${last.id}"]`);
            if (newItem) {
                newItem.classList.add('selected');
                this.selectedItem = newItem;
                this.updateAttachmentDetails(last);
                this.setFeaturedImageBtn.disabled = false;
            }
        }

        // ── Brief pause so user sees "Done" state, then switch tab ─────────
        setTimeout(() => {
            queueEl.remove();
            this._switchToLibraryTab();
        }, 900);
    }

    /**
     * Switch the modal from the Upload tab to the Media Library tab.
     * Uses Bootstrap's Tab API when available for a proper animated transition.
     */
    _switchToLibraryTab() {
        const mediaLibraryTab = document.getElementById(`media-library-tab-${this.modalId}`);
        const mediaLibraryContent = document.getElementById(`media-library-content-${this.modalId}`);
        const uploadTab    = document.getElementById(`upload-tab-${this.modalId}`);
        const uploadContent = document.getElementById(`upload-content-${this.modalId}`);

        if (!mediaLibraryTab || !uploadTab) return;

        // Prefer Bootstrap's Tab API (handles aria-selected, animation, etc.)
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(mediaLibraryTab).show();
        } else {
            // Fallback: manual class toggling
            uploadTab.classList.remove('active');
            if (uploadContent) uploadContent.classList.remove('show', 'active');
            mediaLibraryTab.classList.add('active');
            if (mediaLibraryContent) mediaLibraryContent.classList.add('show', 'active');
        }
    }

    /**
     * Build the upload-queue card that is shown inside the drop zone while files upload.
     * Returns the container element (not yet in the DOM — caller appends it).
     */
    _buildUploadQueue(files) {
        const iconFor = (mime) => {
            if (!mime) return 'ri-file-line text-muted';
            if (mime.startsWith('image/'))     return 'ri-image-line text-primary';
            if (mime.startsWith('video/'))     return 'ri-video-line text-info';
            if (mime === 'application/pdf')    return 'ri-file-pdf-line text-danger';
            if (mime.startsWith('audio/'))     return 'ri-music-line text-warning';
            return 'ri-file-line text-muted';
        };
        const truncate = (str, max) =>
            str.length > max ? str.substring(0, max - 1) + '…' : str;

        const wrap = document.createElement('div');
        wrap.className = 'upload-queue-wrap mt-3 pt-3 border-top';

        const count = files.length;
        wrap.innerHTML = `
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="spinner-border spinner-border-sm text-primary upload-queue-spinner flex-shrink-0" role="status"></span>
                <span class="fw-semibold small upload-queue-hdr-text">
                    Uploading ${count} file${count > 1 ? 's' : ''}…
                </span>
            </div>
            <div class="upload-queue-list vstack gap-2"></div>
        `;

        const list = wrap.querySelector('.upload-queue-list');
        files.forEach(file => {
            const row = document.createElement('div');
            row.className = 'upload-queue-item d-flex align-items-center gap-2 rounded px-2 py-2 bg-light border';
            row.innerHTML = `
                <i class="${iconFor(file.type)} flex-shrink-0 fs-5"></i>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <span class="small fw-medium text-truncate upload-item-name"
                              title="${file.name}">${truncate(file.name, 40)}</span>
                        <span class="upload-item-badge badge"
                              style="font-size:.62rem;white-space:nowrap;background:var(--bs-secondary-bg);color:var(--bs-secondary-color);">
                            Waiting…
                        </span>
                    </div>
                    <div class="progress upload-item-progress" style="height:3px;border-radius:2px;overflow:hidden;">
                        <div class="progress-bar" role="progressbar" style="width:0%;transition:width .12s linear;"></div>
                    </div>
                </div>
                <button type="button" class="upload-item-cancel btn-close flex-shrink-0"
                        aria-label="Cancel" style="display:none;font-size:.55rem;"></button>
            `;
            list.appendChild(row);
        });

        return wrap;
    }

    _showUploadAlerts(dangerMessages, warningMessages) {
        const dropZone = this.dropZone || document.body;
        dropZone.querySelectorAll('.alert-danger, .alert-warning').forEach(el => el.remove());

        const render = (messages, cls, title) => {
            if (!messages.length) return;
            let html = `<div class='alert ${cls} alert-dismissible fade show' role='alert' style='margin-bottom:1rem;'>`;
            html += `<strong>${title}</strong><ul style='margin-bottom:0;'>`;
            messages.forEach(m => { html += `<li>${m}</li>`; });
            html += '</ul>';
            html += `<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>`;
            html += '</div>';
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            dropZone.prepend(tempDiv.firstChild);
        };

        render(dangerMessages, 'alert-danger', 'Upload Error');
        render(warningMessages, 'alert-warning', 'Some files were skipped');
    }

    /** Update a single queue row's badge + progress bar. */
    _setQueueRow(rowEl, status, pct, errorMessage) {
        if (!rowEl) return;
        const bar    = rowEl.querySelector('.progress-bar');
        const badge  = rowEl.querySelector('.upload-item-badge');
        const cancel = rowEl.querySelector('.upload-item-cancel');

        const map = {
            waiting:  { bg: 'bg-secondary-subtle', color: 'text-secondary', barCls: '',            label: 'Waiting…'   },
            uploading:{ bg: 'bg-primary-subtle',   color: 'text-primary',   barCls: 'bg-primary',  label: pct + '%'    },
            done:     { bg: 'bg-success-subtle',   color: 'text-success',   barCls: 'bg-success',  label: '✓ Done'     },
            error:    { bg: 'bg-danger-subtle',    color: 'text-danger',    barCls: 'bg-danger',   label: '✗ Failed'   },
            aborted:  { bg: 'bg-secondary-subtle', color: 'text-secondary', barCls: '',            label: 'Cancelled'  },
        };
        const s = map[status] || map.waiting;
        let label = s.label;
        if (status === 'error' && errorMessage) {
            label = errorMessage.length > 48 ? errorMessage.slice(0, 45) + '…' : errorMessage;
        }

        if (badge) {
            badge.className = `upload-item-badge badge ${s.bg} ${s.color}`;
            badge.style.fontSize = '.62rem';
            badge.textContent    = label;
            badge.title = (status === 'error' && errorMessage) ? errorMessage : '';
        }
        if (bar) {
            bar.className   = `progress-bar ${s.barCls}`;
            bar.style.width = (status === 'done' ? 100 : status === 'uploading' ? pct : 0) + '%';
        }
        if (cancel) {
            cancel.style.display = (status === 'uploading') ? '' : 'none';
        }
    }

    _xhrUploadOneLibraryFile(file, progressRow = null) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            const uploadPropertyId = this._getUploadPropertyId();
            if (uploadPropertyId) {
                formData.append('property_id', String(uploadPropertyId));
            }
            let title = file.name.replace(/\.[^/.]+$/, '');
            title = title.replace(/[^a-zA-Z0-9\s]/g, '').trim();
            formData.append('title', title);

            // ── Wire up cancel button in the queue row ─────────────────────
            const cancelBtn = progressRow && progressRow.querySelector('.upload-item-cancel');
            let xhr = null;

            if (cancelBtn) {
                setTimeout(() => { cancelBtn.style.display = ''; }, 300);
                cancelBtn.addEventListener('click', () => {
                    if (xhr) xhr.abort();
                    reject(new Error('aborted'));
                }, { once: true });
            }

            xhr = new XMLHttpRequest();
            xhr.open('POST', this.getUploadUrl(), true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && progressRow) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    this._setQueueRow(progressRow, 'uploading', pct);
                }
            });

            xhr.onload = () => {
                try {
                    const data = JSON.parse(xhr.responseText || '{}');
                    if (xhr.status === 200 && data.success) {
                        resolve(data);
                        return;
                    }
                    const fileErrors = data.errors && data.errors.file;
                    const firstFileError = Array.isArray(fileErrors) ? fileErrors[0] : null;
                    throw new Error(
                        data.error || firstFileError || data.message || 'Upload failed'
                    );
                } catch (error) {
                    reject(error);
                }
            };

            xhr.onerror = () => reject(new Error('network'));

            xhr.send(formData);
        });
    }

    handleFileSelect(e) {
        const files = e.target.files;
        if (files.length > 0) {
            void this.uploadFiles(files);
        }
        // Always reset the file input so selecting the same file again triggers change
        this.fileInput.value = '';
    }

    handleSetFeaturedImage() {
        if (this.multiple) {
            // ── Multi-select confirm ───────────────────────────────────────
            // Use selectedItemIds (persistent across filter changes) — NOT selectedItems
            // (which only tracks DOM elements in the current visible page).
            if (this.selectedItemIds.size === 0) return;

            // Build normalised item objects from cache using persistent IDs
            const items = Array.from(this.selectedItemIds).map(id => {
                const found = this.mediaItemsCache.find(
                    i => String(i.id) === String(id) || String(i.ID) === String(id)
                );
                if (!found) return null;

                // Validate forced type if set
                if (this.forcedType) {
                    const mime = found.mime_type || '';
                    const typeOk = this.forcedType === 'image' ? mime.startsWith('image/')
                        : this.forcedType === 'video' ? mime.startsWith('video/')
                        : this.forcedType === 'pdf'   ? mime === 'application/pdf'
                        : true;
                    if (!typeOk) return null; // silently skip mis-typed items
                }
                if (this._requires360() && !this._is360Item(found)) return null;

                return {
                    id:          found.id || found.ID,
                    is_360:      this._is360Item(found),
                    url:         resolveMediaUrl(found.url),
                    title:       found.title       || '',
                    alt_text:    found.alt_text    || '',
                    alt:         found.alt_text    || '',
                    caption:     found.caption     || '',
                    description: found.description || '',
                    mime_type:   found.mime_type   || '',
                    filename:    found.filename    || found.file_name || '',
                    file_name:   found.file_name   || found.filename  || '',
                    width:       found.width       || null,
                    height:      found.height      || null,
                    size:        found.size        || null,
                    date:        found.date        || null,
                };
            }).filter(Boolean);

            if (items.length === 0) {
                if (this._requires360()) this._show360RequiredToast();
                return;
            }

            if (typeof this.imagesSelectedCallback === 'function') {
                this.imagesSelectedCallback(items);
            } else {
                // Fallback: fire single-item callback for each
                items.forEach(item => this.imageSelectedCallback(item));
            }

        } else {
            // ── Single-select confirm ─────────────────────────────────────
            if (!this.selectedItem) return;

            // Validate forced type
            if (this.forcedType) {
                const itemType = this.selectedItem.dataset.type;
                if (!this._isTypeAllowed(itemType)) {
                    const labels = { image: 'an image', video: 'a video', pdf: 'a PDF file' };
                    showToast(
                        'Please select ' + (labels[this.forcedType] || 'the correct file type') + '.',
                        'error'
                    );
                    return;
                }
            }

            const selectedId = this.selectedItem.dataset.id;
            const mediaItem  = this.mediaItemsCache.find(
                item => item.id == selectedId || item.ID == selectedId
            );

            if (!mediaItem) {
                console.error('Media item not found in cache');
                return;
            }

            if (this._requires360() && !this._is360Item(mediaItem)) {
                this._show360RequiredToast();
                return;
            }

            this.imageSelectedCallback({
                id:          selectedId,
                is_360:      this._is360Item(mediaItem),
                url:         resolveMediaUrl(mediaItem.url),
                alt:         document.getElementById('altText')?.value    || mediaItem.alt_text || '',
                alt_text:    document.getElementById('altText')?.value    || mediaItem.alt_text || '',
                title:       document.getElementById('imageTitle')?.value || mediaItem.title    || '',
                caption:     document.getElementById('imageCaption')?.value || mediaItem.caption || '',
                description: document.getElementById('imageDescription')?.value || mediaItem.description || '',
                mime_type:   mediaItem.mime_type || '',
                filename:    mediaItem.filename  || '',
                file_name:   mediaItem.file_name || mediaItem.filename || '',
                width:       mediaItem.width  || null,
                height:      mediaItem.height || null,
                size:        mediaItem.size   || null,
                date:        mediaItem.date   || null,
            });
        }

        // Close modal and clean up
        this.modalInstance.hide();
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
        document.body.style.overflow     = '';
        document.body.style.paddingRight = '';
        this.clearSelection();
    }

    open() {
        if (this.modal) {
            this.modal.classList.toggle('media-library-modal--360-picker', this._requires360());
        }
        setMediaLibraryModalTitle(this.modalId, this.title);
        setMediaSetFeaturedButtonText(this.setFeaturedImageBtn, this.setButtonText);
        this.modalInstance.show();
    }

    // refreshMediaItems() {
    //     this.mediaItemsLoaded = false;
    //     this.loadMediaItems();
    // }
}

// Debounce function
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func.apply(this, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(this, args);
    };
}

// Singleton pattern to prevent multiple event listener stacking
window.mediaLibraryInstances = window.mediaLibraryInstances || {};
MediaLibrary._pannellumPromise = null;

window.openMediaLibrary = function(modalId = 'mediaLibraryModal', options = {}) {
    if (!window.mediaLibraryInstances[modalId]) {
        window.mediaLibraryInstances[modalId] = new MediaLibrary({
            modalId,
            ...options
        });
    } else {
        const instance = window.mediaLibraryInstances[modalId];

        // ── Always reset multiple mode on every open call ──────────────────
        // Default to false so single-slot pickers never inherit gallery mode.
        instance.multiple      = !!options.multiple;
        instance.selectedItems   = [];
        instance.selectedItemIds.clear();
        instance.selectedItem  = null;
        if (instance.multiple) {
            instance._updateMultiSelectUI();
        } else {
            instance.setFeaturedImageBtn.disabled = true;
            setMediaSetFeaturedButtonText(
                instance.setFeaturedImageBtn,
                options.setButtonText || instance.setButtonText || 'Select'
            );
        }

        // ── Callbacks ──────────────────────────────────────────────────────
        if (typeof options.onImageSelected === 'function') {
            instance.setImageSelectedCallback(options.onImageSelected);
        }
        if (typeof options.onImagesSelected === 'function') {
            instance.setImagesSelectedCallback(options.onImagesSelected);
        }

        if (options.apiMode === 'common') {
            instance.apiMode = options.apiMode;
        }
        if (options.title) {
            instance.title = options.title;
            setMediaLibraryModalTitle(modalId, options.title);
        }
        if (Object.prototype.hasOwnProperty.call(options, 'setButtonText') && options.setButtonText) {
            instance.setButtonText = options.setButtonText;
            if (!instance.multiple) {
                setMediaSetFeaturedButtonText(
                    document.getElementById(`setFeaturedImageBtn-${modalId}`),
                    options.setButtonText
                );
            }
        }

        // Update forcedType whenever explicitly provided by the caller
        if ('forcedType' in options) {
            instance.forcedType = options.forcedType || null;
            instance.currentFilters.type = instance.forcedType || '';
        }
        instance.options = { ...(instance.options || {}), ...options };
        instance.applyForcedType();
    }

    const inst = window.mediaLibraryInstances[modalId];
    if (inst && options.apiMode === 'common') {
        inst.apiMode = options.apiMode;
    }
    if (inst && options.title) {
        inst.title = options.title;
    }
    if (inst) {
        inst.options = { ...(inst.options || {}), ...options };
        if ('forcedType' in options) {
            inst.forcedType = options.forcedType || null;
            inst.currentFilters.type = inst.forcedType || '';
        }
        inst.applyForcedType();
        // Reset pagination state so Load More always starts fresh on each open
        inst.mediaItemsLoaded = false;
        inst.currentPage      = 1;
        inst.allItemsLoaded   = false;
        inst.mediaItemsCache  = [];
        // Clear stale selection state
        inst.selectedItems   = [];
        inst.selectedItemIds.clear();
        // Hide any lingering preview
        if (inst.mediaPreviewWrap) inst.mediaPreviewWrap.classList.add('d-none');
        inst._hide360AttachmentUI();
        if (inst.modal) {
            inst.modal.classList.toggle('media-library-modal--360-picker', inst._requires360());
        }
    }

    window.mediaLibraryInstances[modalId].open();
};
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// Function to show toast notification
function toastTypeClasses(type) {
    switch (type) {
        case 'success':
            return { bg: 'bg-success', text: 'text-white', close: 'btn-close btn-close-white' };
        case 'info':
            return { bg: 'bg-info', text: 'text-white', close: 'btn-close btn-close-white' };
        case 'warning':
            return { bg: 'bg-warning', text: 'text-dark', close: 'btn-close' };
        default:
            return { bg: 'bg-danger', text: 'text-white', close: 'btn-close btn-close-white' };
    }
}

function showToast(message, type = 'error') {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        // Ensure high z-index to appear above modals
        toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1100;';
        document.body.appendChild(toastContainer);
    }

    const styles = toastTypeClasses(type);
    const toast = document.createElement('div');
    toast.className = `toast align-items-center ${styles.text} ${styles.bg} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="${styles.close} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 5000 }); // Add delay for auto-hide
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

window.showToast = showToast;


// Export for use in other files
window.MediaLibrary = MediaLibrary;
