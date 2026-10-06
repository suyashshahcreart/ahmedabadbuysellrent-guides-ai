function mgAdminRouteBase() {
    if (typeof window.getAdminRouteBase === 'function') {
        return window.getAdminRouteBase();
    }

    const appBase = (window.appConfig && window.appConfig.baseUrl)
        ? String(window.appConfig.baseUrl).replace(/\/$/, '')
        : String(window.appBaseUrl || window.location.origin).replace(/\/$/, '');
    const adminPath = String(window.adminBasePath || 'ppadmlog').replace(/^\/+|\/+$/g, '');

    return `${appBase}/${adminPath}`;
}

document.addEventListener('DOMContentLoaded', function() {

    // Get permissions first
    const mediaGrid = document.getElementById('mediaGrid');
    const permissions = {
        canEdit: mediaGrid ? mediaGrid.dataset.canEdit === 'true' : false,
        canDelete: mediaGrid ? mediaGrid.dataset.canDelete === 'true' : false,
        canUpload: mediaGrid ? mediaGrid.dataset.canUpload === 'true' : false
    };
    // DOM Elements
    const elements = {
        mediaGrid: mediaGrid,
        searchInput: document.getElementById('searchInput'),
        mediaScopeFilter: document.getElementById('mediaScopeFilter'),
        mediaTypeFilter: document.getElementById('mediaTypeFilter'),
        dateFilter: document.getElementById('dateFilter'),
        sortFilter: document.getElementById('sortFilter'),
        mediaPropertyFilter: document.getElementById('mediaPropertyFilter'),
        mediaContextFilter: document.getElementById('mediaContextFilter'),
        media360Filter: document.getElementById('media360Filter'),
        mediaUsageFilter: document.getElementById('mediaUsageFilter'),
        progressBarInner: document.querySelector('.progress-bar'),
        showingCount: document.getElementById('showingCount'),
        totalCount: document.getElementById('totalCount'),
        loadMoreWrap: document.getElementById('mediaLoadMoreWrap'),
        deleteMediaModal: permissions.canDelete ? document.getElementById('deleteMediaModal') : null,
        // Only initialize upload-related elements if upload permission exists
        uploadForm: permissions.canUpload ? document.getElementById('uploadMediaForm') : null,
        uploadButton: permissions.canUpload ? document.getElementById('uploadButton') : null,
        resetUploadBtn: permissions.canUpload ? document.getElementById('resetUploadBtn') : null,
        uploadDropzone: permissions.canUpload ? document.getElementById('uploadDropzone') : null,
        fileInput: permissions.canUpload ? document.getElementById('fileInput') : null,
        selectFilesBtn: permissions.canUpload ? document.getElementById('selectFilesBtn') : null,
        uploadProgress: permissions.canUpload ? document.getElementById('uploadProgress') : null,
        progressBar: permissions.canUpload ? (document.getElementById('uploadProgress') ? document.getElementById('uploadProgress').querySelector('.progress-bar') : null) : null,
        uploadStatus: permissions.canUpload ? document.getElementById('uploadStatus') : null,
        previewContainer: permissions.canUpload ? document.getElementById('previewContainer') : null
    };

    function resolveMediaUrl(urlString) {
        if (!urlString) return '';
        const s = String(urlString).trim();
        if (/^https?:\/\//i.test(s)) return s;
        const base = (window.appConfig && window.appConfig.baseUrl)
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

    function gridImageFitClass(item) {
        if (item.panorama_thumb_path && item.panorama_thumb_url) {
            return 'fit-cover';
        }
        if (item.is_360) {
            return 'fit-contain fit-panorama';
        }
        const w = item.metadata && item.metadata.width;
        const h = item.metadata && item.metadata.height;
        if (w && h) {
            const ratio = w / h;
            if (ratio >= 1.8 || ratio <= 0.55) {
                return 'fit-contain';
            }
        }
        return 'fit-cover';
    }

    function gridPreviewImageUrl(item) {
        if (item.panorama_thumb_path && item.panorama_thumb_url) {
            return resolveMediaUrl(item.panorama_thumb_url);
        }
        return resolveMediaUrl(item.url);
    }

    // State management
    const state = {
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        allItemsLoaded: false,
        isFetching: false,
        /** Align with `MediaLibraryController` list API (18 items per page) */
        filters: {
            search: '',
            type: '',
            date: '',
            sort: 'newest',
            /** `all` = every row in `media`; `library` = only files attached to the shared MediaLibrary (singleton); `unassigned` = no usages. */
            scope: 'all',
            property_id: '',
            context: '',
            usage: '',
            is_360: '',
            page: 1,
            per_page: 18
        },
        permissions: permissions
    };

    if(elements.uploadProgress){
        elements.progressBar = elements.uploadProgress.querySelector('.progress-bar');
        elements.progressBarInner = elements.uploadProgress.querySelector('.progress');
    }

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found: add <meta name="csrf-token" content="{{ csrf_token() }}"> to your layout');
    }

    // Global variable to store current media ID and navigation
    let currentMediaId = null;
    let mediaItems = [];
    let currentMediaIndex = -1;

    // Function to show loading state
    function showLoading() {
        elements.mediaGrid.classList.add('loading');
        if (elements.mediaScopeFilter) elements.mediaScopeFilter.disabled = true;
        if (elements.mediaTypeFilter) elements.mediaTypeFilter.disabled = true;
        if (elements.dateFilter) elements.dateFilter.disabled = true;
        if (elements.sortFilter) elements.sortFilter.disabled = true;
        if (elements.searchInput) elements.searchInput.disabled = true;
        if (elements.mediaPropertyFilter) elements.mediaPropertyFilter.disabled = true;
        if (elements.mediaContextFilter) elements.mediaContextFilter.disabled = true;
        if (elements.media360Filter) elements.media360Filter.disabled = true;
        if (elements.mediaUsageFilter) elements.mediaUsageFilter.disabled = true;
    }

    // Function to hide loading state
    function hideLoading() {
        elements.mediaGrid.classList.remove('loading');
        if (elements.mediaScopeFilter) elements.mediaScopeFilter.disabled = false;
        if (elements.mediaTypeFilter) elements.mediaTypeFilter.disabled = false;
        if (elements.dateFilter) elements.dateFilter.disabled = false;
        if (elements.sortFilter) elements.sortFilter.disabled = false;
        if (elements.searchInput) elements.searchInput.disabled = false;
        if (elements.mediaPropertyFilter) elements.mediaPropertyFilter.disabled = false;
        if (elements.mediaContextFilter) elements.mediaContextFilter.disabled = false;
        if (elements.media360Filter) elements.media360Filter.disabled = false;
        if (elements.mediaUsageFilter) elements.mediaUsageFilter.disabled = false;
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

    const mediaDetailsModalEl = document.getElementById('mediaDetailsModal');
    let mg360Editor = null;
    if (typeof Media360Editor !== 'undefined') {
        mg360Editor = new Media360Editor({
            idPrefix: 'mg360',
            idSuffix: '',
            parentModalEl: mediaDetailsModalEl,
            resolveMediaUrl: resolveMediaUrl,
            showToast: showToast,
            onSaved: function (mediaId, patch) {
                const idx = mediaItems.findIndex(item => Number(item.id) === Number(mediaId));
                if (idx >= 0) {
                    Object.assign(mediaItems[idx], patch);
                }
                const card = elements.mediaGrid?.querySelector(`.media-item[data-id="${mediaId}"]`);
                if (card) {
                    const img = card.querySelector('.media-grid-preview-wrap img');
                    if (img && patch.panorama_thumb_url) {
                        const url = gridPreviewImageUrl({ ...mediaItems[idx] });
                        img.src = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();
                    }
                }
                const previewContainer = document.querySelector('#mediaDetailsModal .preview-container');
                if (previewContainer && patch.panorama_thumb_url && mg360Editor) {
                    const previewUrl = mg360Editor.getPreviewUrl({ ...mediaItems[idx], ...patch });
                    previewContainer.innerHTML = `<img src="${previewUrl}" class="img-fluid ml-360-sidebar-preview" alt="">`;
                }
            },
        });
    }
    if (mediaDetailsModalEl && mg360Editor) {
        mediaDetailsModalEl.addEventListener('hidden.bs.modal', function () {
            mg360Editor.hide();
        });
    }

    // Function to show error message
    function showError(message) {
        console.error(message);
        showToast(message, 'error');
    }

    // Function to show success message
    function showSuccess(message) {
        showToast(message, 'success');
    }

    // Function to fetch media items
    async function fetchMediaItems(append = false) {
        if (state.isFetching) return null;

        if (!append) {
            state.filters.page = 1;
            state.allItemsLoaded = false;
        }

        state.isFetching = true;
        if (!append) {
            showLoading();
        }

        try {
            const queryParams = new URLSearchParams();
            Object.entries(state.filters).forEach(function (entry) {
                const key = entry[0];
                const value = entry[1];
                if (value !== '' && value != null) {
                    queryParams.set(key, value);
                }
            });
            const base = (window.appConfig && window.appConfig.baseUrl) ? window.appConfig.baseUrl.replace(/\/$/, '') : '';
            const apiUrl = `${mgAdminRouteBase()}/media/json?${queryParams.toString()}`;

            const selectedScope = state.filters.scope;
            const selectedType = elements.mediaTypeFilter ? elements.mediaTypeFilter.value : '';
            const selectedDate = elements.dateFilter ? elements.dateFilter.value : '';
            const searchValue = elements.searchInput ? elements.searchInput.value : '';
            const wasFocused = elements.searchInput && document.activeElement === elements.searchInput;

            const response = await fetch(apiUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                const items = data.data.items || [];

                state.currentPage = parseInt(data.data.current_page, 10) || 1;
                state.totalPages = parseInt(data.data.last_page, 10) || 1;
                state.totalItems = parseInt(data.data.total, 10) || 0;
                state.filters.page = state.currentPage;
                state.allItemsLoaded = state.currentPage >= state.totalPages;

                if (append) {
                    mediaItems = [...mediaItems, ...items];
                } else {
                    mediaItems = items;
                }

                renderMediaGrid(items, append);
                updatePagination();

                if (elements.mediaScopeFilter) elements.mediaScopeFilter.value = selectedScope;
                if (elements.mediaTypeFilter) elements.mediaTypeFilter.value = selectedType;
                if (elements.dateFilter) elements.dateFilter.value = selectedDate;

                if (elements.searchInput && wasFocused) {
                    elements.searchInput.value = searchValue;
                    elements.searchInput.focus();
                    const length = searchValue.length;
                    elements.searchInput.setSelectionRange(length, length);
                }

                const urlParams = new URLSearchParams();
                Object.entries(state.filters).forEach(function (entry) {
                    const key = entry[0];
                    const value = entry[1];
                    if (value !== '' && value != null) {
                        urlParams.set(key, value);
                    }
                });
                if (state.currentPage <= 1) {
                    urlParams.delete('page');
                }
                const qs = urlParams.toString();
                const newUrl = qs ? `${window.location.pathname}?${qs}` : window.location.pathname;
                window.history.pushState({ path: newUrl }, '', newUrl);

                return data.data;
            }
            throw new Error(data.message || 'Failed to fetch media items');
        } catch (error) {
            console.error('Error fetching media items:', error);
            if (!append) {
                showError('Failed to load media items. Please try again.');
            } else {
                showToast('Failed to load more items. Please try again.', 'error');
            }
            return null;
        } finally {
            state.isFetching = false;
            if (!append) {
                hideLoading();
            }
        }
    }

    /**
     * Load More button + item count (same pattern as media library modal).
     */
    function updatePagination() {
        if (!elements.loadMoreWrap) return;

        const total = state.totalItems || 0;
        const loaded = mediaItems.length;
        const hasMore = !state.allItemsLoaded && state.currentPage < state.totalPages;

        let html = '';

        if (hasMore) {
            const remaining = Math.max(0, total - loaded);
            html += `<p class="media-items-count-hint mb-2">Showing ${loaded} of ${total} item${total !== 1 ? 's' : ''}</p>`;
            html += `
                <button type="button" class="btn btn-primary btn-sm px-4 media-load-more-btn">
                    <i class="ri-arrow-down-line me-1"></i>Load more
                    <span class="ms-1 small opacity-75">(${remaining} remaining)</span>
                </button>`;
        } else if (total > 0) {
            const allMsg = loaded < total
                ? `Showing ${loaded} of ${total} items`
                : `Showing all ${total} item${total !== 1 ? 's' : ''}`;
            html += `<p class="media-items-count-hint mb-0">${allMsg}</p>`;
        } else {
            html += `<p class="media-items-count-hint mb-0">No items found</p>`;
        }

        elements.loadMoreWrap.innerHTML = html;

        if (hasMore) {
            const btn = elements.loadMoreWrap.querySelector('.media-load-more-btn');
            if (btn) {
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Loading…';
                    state.filters.page = state.currentPage + 1;
                    await fetchMediaItems(true);
                });
            }
        }
    }

    // Function to render media preview
    function renderMediaPreview(item) {
        const mime = item.mime_type || '';
        const fileName = item.filename || item.title || 'File';
        const fileType = mime.split('/')[1] ? mime.split('/')[1].toUpperCase() : 'FILE';
        const placeholderDataUrl = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjxwYXRoIGQ9Ik03NSA2NiBMOTAgODEgTDc1IDk2IiBzdHJva2U9IiNkZWUyZTYiIGZpbGw9Im5vbmUiIHN0cm9rZS13aWR0aD0iMiIvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM2Yzc1N2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
        if (mime.startsWith('image/')) {
            const thumb = gridPreviewImageUrl(item) || placeholderDataUrl;
            const fitClass = gridImageFitClass(item);
            const badge = item.is_360 ? '<span class="badge bg-dark position-absolute top-0 end-0 m-1" style="font-size:.65rem;z-index:3;">360°</span>' : '';
            return `<div class="media-grid-preview-wrap position-relative w-100 h-100">${badge}<img src="${thumb}" class="card-img-top ${fitClass}" alt="${item.alt_text || ''}" onerror="this.onerror=null; this.src='${placeholderDataUrl}'; this.classList.add('placeholder-img','fit-contain');"></div>`;
        } else if (mime.startsWith('video/')) {
            return `<video src="${item.url}" class="card-img-top" controls style="width:100%;height:100%;object-fit:cover;"></video>`;
        } else if (mime === 'application/pdf') {
            // return `<div class="pdf-document-preview">
            //     <div class="pdf-icon-container">
            //         <i class="bi bi-file-earmark-pdf pdf-icon"></i>
            //     </div>
            //     <div class="pdf-info">
            //         <div class="pdf-title">${fileName}</div>
            //         <div class="pdf-type">PDF Document</div>
            //     </div>
            // </div>`;

            return `<div class="file-icon pdf">
                <i class="ri-file-pdf-line" style="color:#dc3545;"></i>
                <div class="file-info-label">${fileType}</div>
                <div class="file-info-type" title="${fileName}">${fileName}</div>
            </div>`;
        } else if (mime.includes('zip') || mime.includes('rar') || mime.includes('7z')) {
            return `<div class="file-icon archive">
                <i class="ri-file-zip-line"></i>
                <div class="file-info-label">${fileType}</div>
                <div class="file-info-type" title="${fileName}">${fileName}</div>
            </div>`;
        } else {
            return `<div class="file-icon">
                <i class="ri-file-line"></i>
                <div class="file-info-label">${fileType}</div>
                <div class="file-info-type" title="${fileName}">${fileName}</div>
            </div>`;
        }
    }

    function escapeHtmlMedia(text) {
        if (text == null) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    /** SweetAlert HTML when delete is blocked (HTTP 409 + usages from API). */
    function formatDeleteBlockedHtml(data) {
        const msg = data && data.message ? String(data.message) : 'This file cannot be deleted while it is still in use.';
        let html = '<p class="mb-0 text-start">' + escapeHtmlMedia(msg) + '</p>';
        const usages = data && Array.isArray(data.usages) ? data.usages : [];
        if (usages.length) {
            html += '<ul class="text-start small mb-0 mt-3 ps-3">';
            usages.forEach(function (u) {
                const label = String(u.subject_label || '—');
                const ctx = String(u.context || '');
                html += '<li>'
                    + escapeHtmlMedia(label)
                    + (ctx ? ' <span class="text-muted">(' + escapeHtmlMedia(ctx) + ')</span>' : '')
                    + '</li>';
            });
            html += '</ul>';
        }
        return html;
    }

    async function deleteMediaRequest(mediaId, force) {
        const delUrl = new URL(`${mgAdminRouteBase()}/media/${mediaId}`, window.location.origin);
        delUrl.searchParams.set('scope', state.filters.scope || 'all');
        if (force) {
            delUrl.searchParams.set('force', '1');
        }
        const response = await fetch(delUrl.toString(), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        let data;
        try {
            data = await response.json();
        } catch (e) {
            data = { success: false, message: 'Invalid response from server.' };
        }
        return { response, data };
    }

    async function finalizeGalleryDeleteSuccess(data) {
        state.filters.page = 1;
        await fetchMediaItems(false);
        await Swal.fire({
            title: 'Deleted!',
            text: 'The media item has been deleted.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    /**
     * Usage row removed from grid cards — see Attachment details → Used in.
     */
    function formatMediaUsagesRow() {
        return '';
    }

    function appendMediaGridItem(item) {
        const mediaItem = document.createElement('div');
        mediaItem.className = 'media-item';

        const mediaId = item.id || item.ID || item.post_id;
        if (!mediaId) {
            console.error('No ID found for media item:', item);
            return;
        }

        mediaItem.dataset.id = mediaId;
        mediaItem.dataset.type = item.type;

        mediaItem.innerHTML = `
                <div class="card-img-container">
                    ${renderMediaPreview(item)}
                    <div class="card-body">
                        <div class="action-buttons">
                            <button class="btn btn-outline-primary view-details" data-id="${mediaId}">
                                <i class="ri-eye-line"></i>
                            </button>
                            ${state.permissions.canDelete ? `
                            <button class="btn btn-outline-danger delete-media" data-id="${mediaId}">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                                ` : ''}
                        </div>
                    </div>
                </div>
                ${formatMediaUsagesRow(item)}
            `;
        elements.mediaGrid.appendChild(mediaItem);
    }

    // Function to render media grid
    function renderMediaGrid(items, append = false) {
        if (!Array.isArray(items)) {
            console.error('Invalid items data:', items);
            return;
        }

        if (!append) {
            elements.mediaGrid.innerHTML = '';

            if (items.length === 0) {
                elements.mediaGrid.innerHTML = `
                <div class="text-center text-muted p-4">
                    <i class="ri-image-2-line fs-2"></i>
                    <p class="mt-2">No media items found</p>
                </div>
            `;
                return;
            }
        } else if (items.length === 0) {
            return;
        }

        if (append) {
            const emptyMsg = elements.mediaGrid.querySelector('.text-center.text-muted');
            if (emptyMsg) emptyMsg.remove();
        }

        items.forEach(item => appendMediaGridItem(item));

        addEventListeners();
    }

    // Function to apply filters
    async function applyFilters() {
        // Reset to first page when filters change
        state.filters.page = 1;
        await fetchMediaItems();
    }

    // Function to add event listeners
    function addEventListeners() {
        // Remove existing event listeners before adding new ones
        if (elements.searchInput) {
            elements.searchInput.removeEventListener('input', searchHandler);
            elements.searchInput.removeEventListener('keydown', searchKeyHandler);
            elements.searchInput.addEventListener('input', searchHandler);
            elements.searchInput.addEventListener('keydown', searchKeyHandler);
        }

        if (elements.mediaTypeFilter) {
            elements.mediaTypeFilter.removeEventListener('change', typeHandler);
            elements.mediaTypeFilter.addEventListener('change', typeHandler);
        }

        if (elements.mediaScopeFilter) {
            elements.mediaScopeFilter.removeEventListener('change', scopeHandler);
            elements.mediaScopeFilter.addEventListener('change', scopeHandler);
        }

        if (elements.dateFilter) {
            elements.dateFilter.removeEventListener('change', dateHandler);
            elements.dateFilter.addEventListener('change', dateHandler);
        }

        if (elements.sortFilter) {
            elements.sortFilter.removeEventListener('change', sortHandler);
            elements.sortFilter.addEventListener('change', sortHandler);
        }

        if (elements.mediaPropertyFilter) {
            elements.mediaPropertyFilter.removeEventListener('change', propertyFilterHandler);
            elements.mediaPropertyFilter.addEventListener('change', propertyFilterHandler);
        }

        if (elements.mediaContextFilter) {
            elements.mediaContextFilter.removeEventListener('change', contextFilterHandler);
            elements.mediaContextFilter.addEventListener('change', contextFilterHandler);
        }

        if (elements.media360Filter) {
            elements.media360Filter.removeEventListener('change', is360FilterHandler);
            elements.media360Filter.addEventListener('change', is360FilterHandler);
        }

        if (elements.mediaUsageFilter) {
            elements.mediaUsageFilter.removeEventListener('change', usageFilterHandler);
            elements.mediaUsageFilter.addEventListener('change', usageFilterHandler);
        }

        // Add event listeners for delete buttons
        document.querySelectorAll('.delete-media').forEach(button => {
            button.removeEventListener('click', deleteHandler);
            button.addEventListener('click', deleteHandler);
        });

        // Add event listeners for view details buttons
        document.querySelectorAll('.view-details').forEach(button => {
            button.removeEventListener('click', viewHandler);
            button.addEventListener('click', viewHandler);
        });
    }

    // Event handler functions
    const searchHandler = debounce(() => {
        if (!elements.searchInput) return;
        
        // Store current cursor position
        const cursorPosition = elements.searchInput.selectionStart;
        
        state.filters.search = elements.searchInput.value.trim();
        applyFilters().then(() => {
            // Restore cursor position after focus
            if (elements.searchInput) {
                elements.searchInput.focus();
                elements.searchInput.setSelectionRange(cursorPosition, cursorPosition);
            }
        });
    }, 1200);

    const typeHandler = () => {
        state.filters.type = elements.mediaTypeFilter.value;
        applyFilters();
    };

    const dateHandler = () => {
        state.filters.date = elements.dateFilter.value;
        applyFilters();
    };

    const sortHandler = () => {
        state.filters.sort = elements.sortFilter.value;
        applyFilters();
    };

    const scopeHandler = () => {
        if (!elements.mediaScopeFilter) return;
        state.filters.scope = elements.mediaScopeFilter.value;
        applyFilters();
    };

    const propertyFilterHandler = () => {
        if (!elements.mediaPropertyFilter) return;
        state.filters.property_id = elements.mediaPropertyFilter.value;
        applyFilters();
    };

    const contextFilterHandler = () => {
        if (!elements.mediaContextFilter) return;
        state.filters.context = elements.mediaContextFilter.value;
        applyFilters();
    };

    const is360FilterHandler = () => {
        if (!elements.media360Filter) return;
        const value = elements.media360Filter.value;
        state.filters.is_360 = (value === '1' || value === '0') ? value : '';
        applyFilters();
    };

    const usageFilterHandler = () => {
        if (!elements.mediaUsageFilter) return;
        const value = elements.mediaUsageFilter.value;
        state.filters.usage = (value === 'referenced' || value === 'unreferenced') ? value : '';
        applyFilters();
    };

    const deleteHandler = async function() {
        const mediaId = this.getAttribute('data-id');

        try {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: 'This media item will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            });

            if (!result.isConfirmed) {
                return;
            }

            let { response, data } = await deleteMediaRequest(mediaId, false);

            if (data.success) {
                await finalizeGalleryDeleteSuccess(data);
                return;
            }

            if (response.status === 409) {
                const proceed = await Swal.fire({
                    title: 'Cannot delete',
                    html: formatDeleteBlockedHtml(data)
                        + '<p class="text-start small text-danger mt-3 mb-0">If you continue, those references will be cleared (or set to empty) and the file will be removed.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete file and clear references',
                    cancelButtonText: 'OK',
                    reverseButtons: true,
                    focusCancel: true,
                    confirmButtonColor: '#dc3545',
                    width: 'min(34rem, 96vw)'
                });

                if (!proceed.isConfirmed) {
                    return;
                }

                ({ response, data } = await deleteMediaRequest(mediaId, true));

                if (data.success) {
                    await finalizeGalleryDeleteSuccess(data);
                    return;
                }

                await Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to delete media item',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            await Swal.fire({
                title: 'Error',
                text: data.message || 'Failed to delete media item',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } catch (error) {
            console.error('Error deleting media item:', error);
            await Swal.fire({
                title: 'Error!',
                text: 'Failed to delete media item',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    };

    // Function to load media details
    async function loadMediaDetails(mediaId) {
        try {
            // Add loading state
            const modal = document.getElementById('mediaDetailsModal');
            modal.classList.add('loading');
            
            // Get current filters
            const filters = {
                type: elements.mediaTypeFilter ? elements.mediaTypeFilter.value : '',
                search: elements.searchInput ? elements.searchInput.value : ''
            };
            
            // Build URL with filters
            const url = new URL(`${mgAdminRouteBase()}/media/${mediaId}`, window.location.origin);
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    url.searchParams.append(key, filters[key]);
                }
            });
            url.searchParams.set('scope', state.filters.scope || 'all');
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                const media = data.data;
                currentMediaId = mediaId;
                
                // Update preview
                const previewContainer = document.querySelector('#mediaDetailsModal .preview-container');
                if (previewContainer) {
                    const officeTypes = [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                    ];
                    if (media.mime_type && media.mime_type.startsWith('image/')) {
                        const previewUrl = (media.is_360 && mg360Editor)
                            ? mg360Editor.getPreviewUrl(media)
                            : media.url;
                        const previewClass = media.is_360 ? 'img-fluid ml-360-sidebar-preview' : 'img-fluid';
                        previewContainer.innerHTML = `<img src="${previewUrl}" class="${previewClass}" alt="${media.alt_text || media.title || ''}">`;
                    } else if (media.mime_type && media.mime_type.startsWith('video/')) {
                        previewContainer.innerHTML = `<video src="${media.url}" class="img-fluid" controls style="max-height:500px;object-fit:contain;"></video>`;
                    } else if (media.mime_type === 'application/pdf') {
                        previewContainer.innerHTML = `<iframe src="${media.url}" style="width:100%;height:500px;border:none;" title="PDF Preview"></iframe>
                            <div class="file-info-type" style="text-align:center;margin-top:8px;" title="${media.filename}">${media.filename}</div>`;
                    } else if (officeTypes.includes(media.mime_type)) {
                        previewContainer.innerHTML = `<iframe src="https://docs.google.com/gview?url=${encodeURIComponent(media.url)}&embedded=true" style="width:100%;height:500px;border:none;" title="Document Preview"></iframe>
                            <div class="file-info-type" style="text-align:center;margin-top:8px;" title="${media.filename}">${media.filename}</div>`;
                    } else {
                        previewContainer.innerHTML = `<div class="file-icon">
                            <i class="ri-file-line"></i>
                            <div class="file-info-label">${media.mime_type && media.mime_type.split('/')[1] ? media.mime_type.split('/')[1].toUpperCase() : 'FILE'}</div>
                            <div class="file-info-type" title="${media.filename}">${media.filename}</div>
                        </div>`;
                    }
                }
                
                // Update form fields
                document.getElementById('mediaTitle').value = media.title || '';
                document.getElementById('mediaAltText').value = media.alt_text || '';
                document.getElementById('mediaCaption').value = media.caption || '';
                document.getElementById('mediaDescription').value = media.description || '';
                document.getElementById('mediaUrl').value = media.url;

                // Wire Document Viewer "View" button
                const viewBtn = document.getElementById('btnViewDocument');
                if (viewBtn) {
                    const viewableMimes = [
                        'application/pdf',
                        'text/plain',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ];
                    const isViewable = (media.mime_type && (
                        media.mime_type.startsWith('image/') ||
                        viewableMimes.includes(media.mime_type)
                    ));
                    viewBtn.style.display = isViewable ? '' : 'none';
                    viewBtn.onclick = function () {
                        if (typeof openDocumentViewer === 'function') {
                            openDocumentViewer({
                                url:      media.url,
                                filename: media.filename,
                                mime:     media.mime_type,
                                title:    media.title || media.filename,
                            });
                        }
                    };
                }
                
                // Update file details
                document.getElementById('mediaDate').textContent = new Date(media.uploaded_at).toLocaleString();
                document.getElementById('mediaUploader').textContent = media.uploaded_by || 'Unknown';
                document.getElementById('mediaFilename').textContent = media.filename;
                document.getElementById('mediaType').textContent = media.mime_type;
                
                document.getElementById('mediaSize').textContent = formatFileSize(media.file_size);
                document.getElementById('mediaDimensions').textContent = media.dimensions || 'Unknown';

                const usagesEl = document.getElementById('mediaUsages');
                if (usagesEl) {
                    const usages = Array.isArray(media.usages) ? media.usages : [];
                    if (usages.length === 0) {
                        usagesEl.textContent = 'Not referenced in the app';
                    } else {
                        usagesEl.innerHTML = '<ul class="mb-0 ps-3 small">' + usages.map(function (u) {
                            return '<li><span class="text-muted">' + (u.context || '') + '</span> — ' + (u.subject_label || '') + '</li>';
                        }).join('') + '</ul>';
                    }
                }

                if (mg360Editor) {
                    mg360Editor.showForMedia(media, permissions.canEdit);
                }

                // Any item in this gallery may be edited when the user has media_upload (not only shared-library rows).
                const editable = permissions.canEdit;
                const saveBtn = document.getElementById('saveMediaDetails');
                if (saveBtn) {
                    saveBtn.disabled = !editable;
                    saveBtn.classList.toggle('disabled', !editable);
                }
                ['mediaTitle', 'mediaAltText', 'mediaCaption', 'mediaDescription'].forEach(function (fid) {
                    const el = document.getElementById(fid);
                    if (el) {
                        el.readOnly = !editable;
                    }
                });

                // Update navigation buttons
                updateNavigationButtons();
                
                // Initialize tooltips
                const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
            } else {
                showToast(data.message || 'Failed to load media details', 'error');
            }
        } catch (error) {
            console.error('Error loading media details:', error);
            showToast('Failed to load media details', 'error');
        } finally {
            // Remove loading state
            const modal = document.getElementById('mediaDetailsModal');
            modal.classList.remove('loading');
        }
    }

    /**
     * Grid order matches the gallery (same sort/filters). Neighbors are index±1 — not DB id±1.
     */
    function getMediaGridIndex() {
        if (!currentMediaId || !Array.isArray(mediaItems) || mediaItems.length === 0) {
            return -1;
        }
        return mediaItems.findIndex(item => Number(item.id) === Number(currentMediaId));
    }

    function setModalNavButton(btn, visible, titleWhenVisible) {
        if (!btn) return;
        btn.disabled = !visible;
        btn.classList.toggle('disabled', !visible);
        btn.classList.toggle('nav-hidden', !visible);
        btn.setAttribute('aria-hidden', visible ? 'false' : 'true');
        btn.title = visible ? titleWhenVisible : '';
    }

    // Function to update navigation buttons
    // #nextMedia = left chevron = previous in grid; #previousMedia = right chevron = next in grid
    function updateNavigationButtons() {
        const leftBtn = document.getElementById('nextMedia');
        const rightBtn = document.getElementById('previousMedia');

        currentMediaIndex = getMediaGridIndex();

        const hasPrev = currentMediaIndex > 0;
        const hasNext = currentMediaIndex >= 0 && currentMediaIndex < mediaItems.length - 1;

        setModalNavButton(leftBtn, hasPrev, 'Previous media item');
        setModalNavButton(rightBtn, hasNext, 'Next media item');
    }

    // Event handlers for navigation buttons
    document.getElementById('nextMedia').addEventListener('click', function() {
        if (!this.disabled && !this.classList.contains('nav-hidden')) {
            loadPreviousMedia();
        }
    });

    document.getElementById('previousMedia').addEventListener('click', function() {
        if (!this.disabled && !this.classList.contains('nav-hidden')) {
            loadNextMedia();
        }
    });

    // Copy URL button functionality
        document.getElementById('copyUrlBtn').addEventListener('click', function() {
            const urlInput = document.getElementById('mediaUrl');
            urlInput.select();
            document.execCommand('copy');
        showToast('URL copied to clipboard', 'success');
    });

    // Delete media functionality
    const deleteMediaFile = document.getElementById('deleteMediaFile');
    if(deleteMediaFile){
    document.getElementById('deleteMediaFile').addEventListener('click', async function(e) {
        e.preventDefault();
        if (!currentMediaId) return;

        const result = await Swal.fire({
            title: 'Delete Permanently',
            text: 'This media item will be permanently deleted and cannot be recovered. Continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            let { response, data } = await deleteMediaRequest(currentMediaId, false);

            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('mediaDetailsModal')).hide();

                const mediaItem = document.querySelector(`.media-item[data-id="${currentMediaId}"]`);
                if (mediaItem) {
                    mediaItem.remove();
                }

                mediaItems = mediaItems.filter(item => item.id !== parseInt(currentMediaId, 10));

                showToast('Media item deleted successfully', 'success');

                currentMediaId = null;
                currentMediaIndex = -1;

                await fetchMediaItems();
                return;
            }

            if (response.status === 409) {
                const proceed = await Swal.fire({
                    title: 'Cannot delete',
                    html: formatDeleteBlockedHtml(data)
                        + '<p class="text-start small text-danger mt-3 mb-0">If you continue, those references will be cleared (or set to empty) and the file will be removed.</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete file and clear references',
                    cancelButtonText: 'OK',
                    reverseButtons: true,
                    focusCancel: true,
                    confirmButtonColor: '#dc3545',
                    width: 'min(34rem, 96vw)'
                });

                if (!proceed.isConfirmed) {
                    return;
                }

                ({ response, data } = await deleteMediaRequest(currentMediaId, true));

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('mediaDetailsModal')).hide();

                    const mediaItem = document.querySelector(`.media-item[data-id="${currentMediaId}"]`);
                    if (mediaItem) {
                        mediaItem.remove();
                    }

                    mediaItems = mediaItems.filter(item => item.id !== parseInt(currentMediaId, 10));

                    showToast('Media item deleted successfully', 'success');

                    currentMediaId = null;
                    currentMediaIndex = -1;

                    await fetchMediaItems();
                    return;
                }

                await Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to delete media item',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            await Swal.fire({
                title: 'Error',
                text: data.message || 'Failed to delete media item',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } catch (error) {
            console.error('Error deleting media item:', error);
            showToast('Failed to delete media item', 'error');
        }
    });
    }

    // Update the view handler to set current media index
    const viewHandler = async function() {
        const mediaId = this.getAttribute('data-id');
        if (!mediaId) {
            console.error('No media ID found for view button');
            showToast('Failed to load media details: No media ID found', 'error');
            return;
        }

        // Find the index of the current media item
        currentMediaIndex = mediaItems.findIndex(item => item.id === parseInt(mediaId));
        
        const modal = document.getElementById('mediaDetailsModal');
        const modalInstance = new bootstrap.Modal(modal);
        
        await loadMediaDetails(mediaId);
        modalInstance.show();
    };

    // Update save handler for all fields
    const saveMediaDetails = document.getElementById('saveMediaDetails');
    if(saveMediaDetails){
    document.getElementById('saveMediaDetails').addEventListener('click', async function() {
        if (!currentMediaId) return;

        const formData = {
            title: document.getElementById('mediaTitle').value.trim(),
            alt_text: document.getElementById('mediaAltText').value.trim(),
            caption: document.getElementById('mediaCaption').value.trim(),
            description: document.getElementById('mediaDescription').value.trim()
        };

        try {
            const response = await fetch(`${mgAdminRouteBase()}/media/${currentMediaId}`, {
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
                
                // Update the media grid item
                updateMediaGridItem(currentMediaId, formData);
                
                // Update the current item in mediaItems array
                const itemIndex = mediaItems.findIndex(item => item.id === parseInt(currentMediaId));
                if (itemIndex !== -1) {
                    mediaItems[itemIndex] = { ...mediaItems[itemIndex], ...formData };
                }
                
                // Reload media details to ensure everything is in sync
                await loadMediaDetails(currentMediaId);
            } else {
                showToast(data.message || 'Failed to update media details', 'error');
            }
        } catch (error) {
            console.error('Error updating media details:', error);
            showToast('Failed to update media details', 'error');
        }
    });
    }

    // Function to update media grid item after save
    function updateMediaGridItem(mediaId, formData) {
        const mediaItem = document.querySelector(`.media-item[data-id="${mediaId}"]`);
        if (mediaItem) {
            const img = mediaItem.querySelector('img');
            if (img) {
                img.alt = formData.alt_text || formData.title || '';
                // Update any other visible details in the grid item if needed
            }
        }
    }

    // Initialize tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Debounce function with immediate option
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const context = this;
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // Handle special keys in search input
    const searchKeyHandler = (e) => {
        // Allow arrow keys, home, end, etc. to work normally
        if ([37, 38, 39, 40, 35, 36].includes(e.keyCode)) {
            return;
        }
        
        // Trigger search immediately on enter
        if (e.keyCode === 13) {
            e.preventDefault();
            state.filters.search = elements.searchInput.value.trim();
            applyFilters();
        }
    };

    // Handle file upload
    const uploadFileInput = document.getElementById('uploadFile');
    const uploadDropzone = document.getElementById('uploadDropzone');
    const previewContainer = document.getElementById('previewContainer');
    const uploadButton = document.getElementById('uploadButton');
    const resetUploadBtn = document.getElementById('resetUploadBtn');

    if (uploadFileInput && uploadDropzone && previewContainer && uploadButton && resetUploadBtn) {
        let selectedFiles = [];

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadDropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadDropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadDropzone.addEventListener('drop', handleDrop, false);
        });

        function highlight(e) {
            uploadDropzone.classList.add('border-primary');
        }

        function unhighlight(e) {
            uploadDropzone.classList.remove('border-primary');
        }

        // Handle dropped files
        uploadDropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            // Clear any previous errors
            clearUploadErrors();
            
            if (files.length === 0) {
                return; // No files dropped
            }
            
            handleFiles(files);
        }

        uploadFileInput.addEventListener('change', function(e) {
            // Clear any previous errors
            clearUploadErrors();
            if (this.files.length === 0) {
                return; // No files selected
            }
            handleFiles(this.files);
            // Always reset file input so selecting the same file again triggers change
            this.value = '';
        });

        function handleFiles(files) {
            // Remove previous error
            const errorContainer = document.getElementById('uploadErrorContainer');
            if (errorContainer) errorContainer.style.display = 'none';
            const allowedTypes = [
                'image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'image/avif',
                'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon',
                'video/mp4', 'video/avi', 'video/webm', 'video/m4v',
                'audio/mp3', 'audio/webm',
                'application/json', 'application/xml', 'text/xml',
                'application/sql', 'text/sql',
                'application/pdf'
            ];
            const isAllowedSvg = (f) =>
                f.name.toLowerCase().endsWith('.svg') &&
                (!f.type || f.type === 'image/svg+xml');
            const isAllowedIco = (f) =>
                f.name.toLowerCase().endsWith('.ico') &&
                (!f.type || f.type === 'image/x-icon' || f.type === 'image/vnd.microsoft.icon');
            const maxSize = 1024 * 1024 * 1024; // 1GB in bytes
            const validFiles = [];
            const invalidFiles = [];
            Array.from(files).forEach(file => {
                if (!allowedTypes.includes(file.type) && !isAllowedSvg(file) && !isAllowedIco(file)) {
                    invalidFiles.push({
                        file: file,
                        error: `File type "${file.type}" is not supported. Please upload only images, videos, audio, JSON, XML, SQL, or PDF files.`
                    });
                    return;
                }
                if (file.size > maxSize) {
                    invalidFiles.push({
                        file: file,
                        error: `File size (${formatFileSize(file.size)}) exceeds the maximum allowed size of 1GB.`
                    });
                    return;
                }
                validFiles.push(file);
            });
            // Show errors for invalid files
            if (invalidFiles.length > 0) {
                const errorMessages = invalidFiles.map(item => `${item.file.name}: ${item.error}`).join('\n');
                showToast(`Some files were rejected:\n${errorMessages}`, 'error');
                // Show global error message
                const errorContainer = document.getElementById('uploadErrorContainer');
                const errorMessage = document.getElementById('uploadErrorMessage');
                if (errorContainer && errorMessage) {
                    errorMessage.innerHTML = invalidFiles.map(item => 
                        `<strong>${item.file.name}:</strong> ${item.error}`
                    ).join('<br>');
                    errorContainer.style.display = 'block';
                }
            }
            // Only proceed with valid files
            if (validFiles.length > 0) {
                selectedFiles = validFiles;
                showPreviews(selectedFiles);
                uploadButton.disabled = false;
            } else {
                uploadButton.disabled = true;
                // Show message if no valid files were found
                if (files.length > 0) {
                    showToast('No valid files found. Please check the file types and sizes.', 'error');
                }
            }
        }

        function showPreviews(files) {
            previewContainer.innerHTML = '';
            files.forEach((file, index) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.dataset.index = index;
                const fileInfo = getFileTypeInfo(file);
                previewItem.classList.add(fileInfo.class);
                if (fileInfo.needsReader) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            previewItem.innerHTML = `
                                <img src="${e.target.result}" alt="${file.name}">
                                <div class="file-tooltip">${file.name}</div>
                                <div class="file-info">${formatFileSize(file.size)}</div>
                            `;
                        } else if (file.type.startsWith('video/')) {
                            previewItem.innerHTML = `
                                <video src="${e.target.result}" muted></video>
                                <div class="file-tooltip">${file.name}</div>
                                <div class="file-info">${formatFileSize(file.size)}</div>
                            `;
                        } else if (file.type.startsWith('audio/')) {
                            previewItem.innerHTML = `
                                <div class="file-preview">
                                    <i class="ri-music-2-line file-icon"></i>
                                    <div class="file-name">${file.name.split('.')[0]}</div>
                                    <audio src="${e.target.result}" controls style="width: 100%; margin-top: 0.5rem;"></audio>
                                </div>
                                <div class="file-tooltip">${file.name}</div>
                                <div class="file-info">${formatFileSize(file.size)}</div>
                            `;
                        }
                        // Add remove button after preview content
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'btn-close remove-file';
                        removeBtn.setAttribute('data-index', index);
                        removeBtn.type = 'button';
                        previewItem.appendChild(removeBtn);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Document files
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    let icon = 'ri-file-line';
                    let color = '#6c757d';
                    if (fileExtension === 'json') { icon = 'ri-file-code-line'; color = '#f39c12'; }
                    else if (fileExtension === 'xml') { icon = 'ri-file-code-line'; color = '#e74c3c'; }
                    else if (fileExtension === 'sql') { icon = 'ri-file-text-line'; color = '#3498db'; }
                    else if (fileExtension === 'pdf') { icon = 'ri-file-pdf-line'; color = '#dc3545'; }
                    previewItem.innerHTML = `
                        <div class="file-preview">
                            <i class="${icon} file-icon" style="color: ${color};"></i>
                            <div class="file-name">${file.name.split('.')[0]}</div>
                            <div class="file-extension">.${fileExtension.toUpperCase()}</div>
                        </div>
                        <div class="file-tooltip">${file.name}</div>
                        <div class="file-info">${formatFileSize(file.size)}</div>
                    `;
                    // Add remove button after preview content
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'btn-close remove-file';
                    removeBtn.setAttribute('data-index', index);
                    removeBtn.type = 'button';
                    previewItem.appendChild(removeBtn);
                }
                previewContainer.appendChild(previewItem);
            });
        }

        // Use event delegation for remove button
        previewContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-file')) {
                const idx = parseInt(e.target.getAttribute('data-index'));
                if (!isNaN(idx)) {
                    selectedFiles.splice(idx, 1);
                    showPreviews(selectedFiles);
                    uploadButton.disabled = selectedFiles.length === 0;
                }
            }
        });

        function getFileTypeInfo(file) {
            const type = file.type.split('/')[0];
            const extension = file.name.split('.').pop().toLowerCase();
            
            switch (type) {
                case 'image':
                    return {
                        icon: 'ri-image-2-line',
                        class: 'image',
                        needsReader: true
                    };
                case 'video':
                    return {
                        icon: 'ri-video-line',
                        class: 'video',
                        needsReader: true
                    };
                case 'audio':
                    return {
                        icon: 'ri-music-2-line',
                        class: 'audio',
                        needsReader: true
                    };
                case 'application':
                    if (extension === 'json') {
                        return {
                            icon: 'ri-file-code-line',
                            class: 'json',
                            needsReader: false
                        };
                    } else if (extension === 'xml') {
                        return {
                            icon: 'ri-file-code-line',
                            class: 'xml',
                            needsReader: false
                        };
                    } else if (extension === 'sql') {
                        return {
                            icon: 'ri-file-text-line',
                            class: 'sql',
                            needsReader: false
                        };
                    } else if (extension === 'pdf' || file.type === 'application/pdf') {
                        return {
                            icon: 'ri-file-pdf-line',
                            class: 'pdf',
                            needsReader: false
                        };
                    }
                    break;
                case 'text':
                    if (extension === 'xml') {
                        return {
                            icon: 'ri-file-code-line',
                            class: 'xml',
                            needsReader: false
                        };
                    } else if (extension === 'sql') {
                        return {
                            icon: 'ri-file-text-line',
                            class: 'sql',
                            needsReader: false
                        };
                    }
                    break;
                default:
                    return {
                        icon: 'ri-file-line',
                        class: 'file',
                        needsReader: false
                    };
            }
        }

        // Function to show error in upload modal
        function showUploadError(message) {
            const errorContainer = document.getElementById('uploadErrorContainer');
            const errorMessage = document.getElementById('uploadErrorMessage');
            
            if (errorContainer && errorMessage) {
                errorMessage.textContent = message;
                errorContainer.style.display = 'block';
                
                // Scroll to error message
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Function to clear upload errors
        function clearUploadErrors() {
            const errorContainer = document.getElementById('uploadErrorContainer');
            if (errorContainer) {
                errorContainer.style.display = 'none';
            }
        }

        // Function to show file-specific error
        function showFileError(fileIndex, errorMessage) {
            const fileItem = document.querySelector(`.upload-file-item[data-index="${fileIndex}"]`);
            if (fileItem) {
                fileItem.classList.add('error');
                
                // Remove existing error message if any
                const existingError = fileItem.querySelector('.file-error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                // Add new error message
                const errorElement = document.createElement('div');
                errorElement.className = 'file-error-message';
                errorElement.textContent = errorMessage;
                fileItem.appendChild(errorElement);
            }
        }

        // Function to show success summary in modal
        function showUploadSuccess(successCount, totalCount) {
            const statusText = document.getElementById('uploadStatus');
            if (statusText) {
                statusText.className = 'upload-status mt-2 text-success';
                statusText.textContent = `Successfully uploaded ${successCount} of ${totalCount} file(s)`;
            }
        }

        // Update the upload button click handler
        uploadButton.addEventListener('click', async function() {
            if (selectedFiles.length === 0) return;

            const uploadProgress = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('uploadProgressBar');
            const statusText = document.getElementById('uploadStatus');
            const fileList = document.getElementById('uploadFileList');
            const totalFiles = selectedFiles.length;

            // Clear any previous errors
            clearUploadErrors();
            
            uploadDropzone.style.display = 'none';
            previewContainer.style.display = 'none';
            uploadProgress.style.display = 'block';
            uploadButton.disabled = true;
            resetUploadBtn.disabled = true;

            let successCount = 0;
            let errorCount = 0;
            let hasErrors = false;

            // Create file list items
            fileList.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'upload-file-item';
                fileItem.dataset.index = index;
                fileItem.innerHTML = `
                    <div class="file-name">${file.name}</div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="file-status"></div>
                `;
                fileList.appendChild(fileItem);
            });

            for (let i = 0; i < selectedFiles.length; i++) {
                const file = selectedFiles[i];
                const formData = new FormData();
                formData.append('file', file);

                try {
                    const progress = Math.round(((i + 1) / selectedFiles.length) * 100);
                    progressBar.style.width = `${progress}%`;
                    progressBar.setAttribute('aria-valuenow', progress);
                    statusText.textContent = `Uploading ${i + 1} of ${selectedFiles.length}: ${file.name}...`;

                    const response = await fetch(`${mgAdminRouteBase()}/media`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        successCount++;
                        if (data.data && data.data.auto_detected_360) {
                            showToast('360° photo detected automatically', 'info');
                        }
                        // Update file item to show success
                        const fileItem = document.querySelector(`.upload-file-item[data-index="${i}"]`);
                        if (fileItem) {
                            fileItem.classList.add('success');
                            const progressBar = fileItem.querySelector('.progress-bar');
                            const statusDiv = fileItem.querySelector('.file-status');
                            if (progressBar) {
                                progressBar.style.width = '100%';
                                progressBar.classList.add('bg-success');
                            }
                            if (statusDiv) {
                                statusDiv.innerHTML = '<i class="ri-checkbox-circle-line text-success"></i>';
                            }
                        }
                        addFileToGrid(data.data);
                    } else {
                        errorCount++;
                        hasErrors = true;
                        showFileError(i, data.message || 'Upload failed');
                        // Update file item to show error
                        const fileItem = document.querySelector(`.upload-file-item[data-index="${i}"]`);
                        if (fileItem) {
                            const statusDiv = fileItem.querySelector('.file-status');
                            if (statusDiv) {
                                statusDiv.innerHTML = '<i class="ri-close-circle-line text-danger"></i>';
                            }
                        }
                        showToast(`Failed to upload ${file.name}: ${data.message}`, 'error');
                    }

                    // Update progress status with current count
                    statusText.textContent = `Processed ${i + 1} of ${totalFiles} files (${successCount} successful)`;

                } catch (error) {
                    errorCount++;
                    hasErrors = true;
                    showFileError(i, error.message || 'Upload failed');
                    // Update file item to show error
                    const fileItem = document.querySelector(`.upload-file-item[data-index="${i}"]`);
                    if (fileItem) {
                        const statusDiv = fileItem.querySelector('.file-status');
                        if (statusDiv) {
                            statusDiv.innerHTML = '<i class="ri-close-circle-line text-danger"></i>';
                        }
                    }
                    showToast(`Error uploading ${file.name}: ${error.message}`, 'error');
                }
            }

            // Show final status
            if (successCount > 0) {
                showUploadSuccess(successCount, totalFiles);
                showToast(`Successfully uploaded ${successCount} file(s)`, 'success');
            }
            
            if (errorCount > 0) {
                showUploadError(`Failed to upload ${errorCount} of ${totalFiles} file(s). Please check the errors below.`);
                // Enable reset button to allow retrying
                resetUploadBtn.disabled = false;
                // Don't close modal if there are errors
            return;
        }

            // Only close modal if all uploads were successful
            setTimeout(() => {
                resetUploadModal();
                const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
                if (modal) {
                    modal.hide();
                }
                fetchMediaItems();
            }, 1000);
        });

        // Function to reset upload modal
        function resetUploadModal() {
            // Reset file input
            uploadFileInput.value = '';
            selectedFiles = [];
            
            // Clear preview container
            previewContainer.innerHTML = '';
            
            // Show dropzone, hide progress
            uploadDropzone.style.display = 'block';
            previewContainer.style.display = 'block';
            uploadProgress.style.display = 'none';
            
            // Reset progress bar
            const progressBar = document.getElementById('uploadProgressBar');
            if (progressBar) {
                progressBar.style.width = '0%';
                progressBar.setAttribute('aria-valuenow', 0);
            }
            
            // Reset status text
            const statusText = document.getElementById('uploadStatus');
            if (statusText) {
                statusText.textContent = 'Preparing upload...';
            }
            
            // Clear file list
            const fileList = document.getElementById('uploadFileList');
            if (fileList) {
                fileList.innerHTML = '';
            }
            
            // Reset buttons
            uploadButton.disabled = true;
            resetUploadBtn.disabled = false;
            
            // Clear any error messages
            clearUploadErrors();
        }

        // Add event listener for reset button
        if (resetUploadBtn) {
            resetUploadBtn.addEventListener('click', function() {
                resetUploadModal();
                // Re-enable file selection
                if (uploadFileInput) {
                    uploadFileInput.value = '';
                    uploadFileInput.disabled = false;
                }
                // Show the dropzone again
                if (uploadDropzone) {
                    uploadDropzone.style.display = 'block';
                }
            });
        }
    } else {
        console.warn('Required upload elements not found');
    }

    // Function to add a file to the grid
    function addFileToGrid(fileData) {
        const mediaGrid = document.getElementById('mediaGrid');
        if (!mediaGrid) return;
        const permissions = {
            canEdit: mediaGrid ? mediaGrid.dataset.canEdit === 'true' : false,
            canDelete: mediaGrid ? mediaGrid.dataset.canDelete === 'true' : false,
            canUpload: mediaGrid ? mediaGrid.dataset.canUpload === 'true' : false
        };

        // Use placeholder if URL is missing initially (rare, but defensive)
        const placeholderDataUrl = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjxwYXRoIGQ9Ik03NSA2NiBMOTAgODEgTDc1IDk2IiBzdHJva2U9IiNkZWUyZTYiIGZpbGw9Im5vbmUiIHN0cm9rZS13aWR0aD0iMiIvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM2Yzc1N2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
        const imageUrl = fileData.url || placeholderDataUrl;
        const altText = fileData.alt_text || fileData.title || '';
        const mediaId = fileData.id;
        const mediaType = fileData.mime_type ? fileData.mime_type.split('/')[0] : 'unknown';

        const mediaItem = document.createElement('div');
        mediaItem.className = 'media-item';
        mediaItem.setAttribute('data-id', mediaId);
        mediaItem.setAttribute('data-type', mediaType);

        // Match the structure of items rendered by PHP/renderMediaGrid
        mediaItem.innerHTML = `
            <div class="card-img-container">
                ${renderMediaPreview(fileData)}
                <div class="card-body">
                    <div class="action-buttons">
                        <button class="btn btn-outline-primary view-details" data-id="${mediaId}">
                            <i class="ri-eye-line"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-media" data-id="${mediaId}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
            </div>
            ${formatMediaUsagesRow(fileData)}
        `;

        // Add to the beginning of the grid
        if (mediaGrid.firstChild) {
            mediaGrid.insertBefore(mediaItem, mediaGrid.firstChild);
        } else {
            // If grid was empty, remove the "No items found" message if it exists
            const noItemsMessage = mediaGrid.querySelector('.text-center.text-muted');
            if (noItemsMessage) {
                noItemsMessage.remove();
            }
            mediaGrid.appendChild(mediaItem);
        }

        // Re-attach event listeners for the new item
        const viewButton = mediaItem.querySelector('.view-details');
        const deleteButton = mediaItem.querySelector('.delete-media');
        if (viewButton) viewButton.addEventListener('click', viewHandler);
        if (deleteButton) deleteButton.addEventListener('click', deleteHandler);
    }

    // Initialize from URL parameters
    function initializeFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        state.filters.page = 1;
        state.filters.per_page = parseInt(urlParams.get('per_page'), 10) || 18;
        state.filters.search = urlParams.get('search') || '';
        state.filters.type = urlParams.get('type') || '';
        state.filters.date = urlParams.get('date') || '';
        state.filters.sort = urlParams.get('sort') || 'newest';
        const scopeParam = urlParams.get('scope');
        state.filters.scope = (['library', 'all', 'unassigned'].includes(scopeParam)) ? scopeParam : 'all';
        if (scopeParam === 'unassigned') {
            state.filters.usage = 'unreferenced';
            state.filters.scope = 'all';
        }
        state.filters.property_id = urlParams.get('property_id') || '';
        state.filters.context = urlParams.get('context') || '';
        const usageParam = urlParams.get('usage');
        if (usageParam === 'referenced' || usageParam === 'unreferenced') {
            state.filters.usage = usageParam;
        }
        const is360Param = urlParams.get('is_360');
        state.filters.is_360 = (is360Param === '1' || is360Param === '0') ? is360Param : '';

        // Set initial values in inputs
        if (elements.searchInput) elements.searchInput.value = state.filters.search;
        if (elements.mediaScopeFilter) elements.mediaScopeFilter.value = state.filters.scope;
        if (elements.mediaTypeFilter) elements.mediaTypeFilter.value = state.filters.type;
        if (elements.dateFilter) elements.dateFilter.value = state.filters.date;
        if (elements.sortFilter) elements.sortFilter.value = state.filters.sort;
        if (elements.mediaPropertyFilter) elements.mediaPropertyFilter.value = state.filters.property_id;
        if (elements.mediaContextFilter) elements.mediaContextFilter.value = state.filters.context;
        if (elements.media360Filter) elements.media360Filter.value = state.filters.is_360;
        if (elements.mediaUsageFilter) elements.mediaUsageFilter.value = state.filters.usage;
    }

    // Initialize the media gallery
    function initializeMediaGallery() {
        // Initialize from URL parameters
        initializeFromUrl();
        
        // Add event listeners
        addEventListeners();
        
        // Fetch initial media items
        fetchMediaItems();
        
        // Initialize tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
        
        // Clear upload errors when modal is opened
        const uploadModal = document.getElementById('uploadModal');
        if (uploadModal) {
            uploadModal.addEventListener('show.bs.modal', function() {
                const errorContainer = document.getElementById('uploadErrorContainer');
                if (errorContainer) {
                    errorContainer.style.display = 'none';
                }
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMediaGallery);
    } else {
        initializeMediaGallery();
    }

    // Next = later row in the current grid (index + 1). Uses gallery order, not numeric DB id.
    async function loadNextMedia() {
        if (!currentMediaId) return;
        const idx = getMediaGridIndex();
        if (idx < 0 || idx >= mediaItems.length - 1) return;
        const nextItem = mediaItems[idx + 1];
        if (!nextItem || nextItem.id == null) return;
        await loadMediaDetails(nextItem.id);
    }

    // Previous = earlier row in the current grid (index - 1).
    async function loadPreviousMedia() {
        if (!currentMediaId) return;
        const idx = getMediaGridIndex();
        if (idx <= 0) return;
        const prevItem = mediaItems[idx - 1];
        if (!prevItem || prevItem.id == null) return;
        await loadMediaDetails(prevItem.id);
    }

    // Handle media navigation
    function handleMediaNavigation(direction, currentId) {
        const endpoint = direction === 'next' ? `${mgAdminRouteBase()}/media/next/${currentId}` : `${mgAdminRouteBase()}/media/previous/${currentId}`;
        
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.media) {
                    updateMediaModal(data.media);
                } else {
                    showToast('error', data.error || 'Navigation failed');
                }
            })
            .catch(error => {
                console.error('Navigation error:', error);
                showToast('error', 'Failed to navigate media');
            });
    }

    // Update media modal content
    function updateMediaModal(media) {
        const modal = document.getElementById('mediaDetailsModal');
        if (!modal) return;

        // Update modal title
        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = media.post_title || 'Media Details';
        }

        // Update media preview
        const mediaPreview = modal.querySelector('.media-preview');
        if (mediaPreview) {
            if (media.post_mime_type.startsWith('image/')) {
                mediaPreview.innerHTML = `<img src="${media.guid}" alt="${media.post_title}" class="img-fluid">`;
            } else {
                mediaPreview.innerHTML = `<div class="document-preview">
                    <i class="fas fa-file-alt fa-3x"></i>
                    <p>${media.post_title}</p>
                </div>`;
            }
        }

        // Update media details
        const detailsContainer = modal.querySelector('.media-details');
        if (detailsContainer) {
            const uploadDate = new Date(media.post_date).toLocaleDateString();
            detailsContainer.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">File URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="${media.guid}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('${media.guid}')">
                            <i class="fas fa-copy"></i> Copy URL
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <p><strong>Uploaded on:</strong> ${uploadDate}</p>
                    <p><strong>File type:</strong> ${media.post_mime_type}</p>
                </div>
            `;
        }

        // Update navigation buttons data-id attribute
        const prevButton = modal.querySelector('.nav-prev');
        const nextButton = modal.querySelector('.nav-next');
        if (prevButton) prevButton.setAttribute('data-id', media.ID);
        if (nextButton) nextButton.setAttribute('data-id', media.ID);

        // Update delete button data-id
        const deleteButton = modal.querySelector('.delete-media');
        if (deleteButton) deleteButton.setAttribute('data-id', media.ID);
    }

    // Add event listeners for navigation buttons
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('mediaDetailsModal');
        if (!modal) return;

        const prevButton = modal.querySelector('.nav-prev');
        const nextButton = modal.querySelector('.nav-next');

        if (prevButton) {
            prevButton.addEventListener('click', function() {
                const currentId = this.getAttribute('data-id');
                if (currentId) handleMediaNavigation('previous', currentId);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function() {
                const currentId = this.getAttribute('data-id');
                if (currentId) handleMediaNavigation('next', currentId);
            });
        }
    });

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('success', 'URL copied to clipboard');
        }).catch(() => {
            showToast('error', 'Failed to copy URL');
        });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
}); 