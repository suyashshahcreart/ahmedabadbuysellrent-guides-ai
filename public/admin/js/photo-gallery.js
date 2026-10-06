/**
 * Photo gallery slide manager — Albergoes media library integration.
 * Supports multi-select to auto-create slides with media metadata mapping.
 */
window.PhotoGalleryManager = (function () {
    var activePickerContainer = null;
    var galleryContainer = null;
    var noImagesMessage = null;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function toggleEmptyState() {
        if (!galleryContainer || !noImagesMessage) return;
        var count = galleryContainer.querySelectorAll('.gallery-item-container').length;
        noImagesMessage.style.display = count === 0 ? 'block' : 'none';
    }

    /**
     * Map media library item fields to gallery slide fields.
     * Media: Title | Alt text | Caption | Description
     * Slide: Image Title | ALT Text | Caption | SEO Title | Tags | SEO Description
     */
    function mapMediaToSlideData(media) {
        media = media || {};
        var title       = (media.title || media.filename || media.file_name || '').trim();
        var altText     = (media.alt_text || media.alt || '').trim();
        var caption     = (media.caption || '').trim();
        var description = (media.description || '').trim();

        return {
            media_id:        media.id,
            media_url:       media.url || '',
            title:           title,
            alt_text:        altText,
            caption:         caption,
            seo_title:       title,
            seo_description: description,
            image_tags:      '',
        };
    }

    function setSlideField(slideEl, selector, value) {
        var field = slideEl.querySelector(selector);
        if (field) {
            field.value = value || '';
        }
    }

    function applyMediaToSlide(slideEl, data) {
        if (!slideEl || !data) return;

        slideEl.querySelector('.gallery-media-id').value = data.media_id || '';
        var img = slideEl.querySelector('.gallery-preview-img');
        if (img) {
            img.src = data.media_url || '';
            img.style.display = data.media_url ? '' : 'none';
            img.alt = data.alt_text || data.title || '';
        }
        var placeholder = slideEl.querySelector('.gallery-preview-placeholder');
        if (placeholder) {
            placeholder.style.display = data.media_url ? 'none' : '';
        }

        setSlideField(slideEl, 'input[name$="[title]"]', data.title);
        setSlideField(slideEl, 'input[name$="[alt_text]"]', data.alt_text);
        setSlideField(slideEl, 'textarea[name$="[caption]"]', data.caption);
        setSlideField(slideEl, 'input[name$="[seo_title]"]', data.seo_title);
        setSlideField(slideEl, 'input[name$="[image_tags]"]', data.image_tags);
        setSlideField(slideEl, 'textarea[name$="[seo_description]"]', data.seo_description);
    }

    function buildItemHtml(index, data) {
        data = data || {};
        var previewStyle = data.media_url ? '' : 'display:none;';
        var previewSrc = escapeHtml(data.media_url || '');
        var placeholderStyle = data.media_url ? 'display:none;' : '';

        return '' +
            '<div class="gallery-item-container border rounded p-3 mb-3 bg-light-subtle" data-index="' + index + '">' +
                '<div class="d-flex justify-content-between align-items-center mb-2">' +
                    '<span class="badge bg-soft-primary text-primary"><i class="ri-image-line me-1"></i>Slide ' + (index + 1) + '</span>' +
                    '<div class="btn-group btn-group-sm">' +
                        '<button type="button" class="btn btn-outline-secondary move-gallery-up" title="Move up"><i class="ri-arrow-up-line"></i></button>' +
                        '<button type="button" class="btn btn-outline-secondary move-gallery-down" title="Move down"><i class="ri-arrow-down-line"></i></button>' +
                        '<button type="button" class="btn btn-outline-danger remove-gallery-item" title="Remove"><i class="ri-delete-bin-line"></i></button>' +
                    '</div>' +
                '</div>' +
                '<div class="row g-3">' +
                    '<div class="col-md-3">' +
                        '<div class="text-center p-2 border rounded bg-white gallery-media-preview" style="min-height:140px;">' +
                            '<img src="' + previewSrc + '" class="img-fluid mb-2 gallery-preview-img" alt="' + escapeHtml(data.alt_text || '') + '" style="max-height:120px;object-fit:cover;' + previewStyle + '">' +
                            '<div class="gallery-preview-placeholder text-muted small" style="' + placeholderStyle + '">' +
                                '<i class="ri-image-line fs-3 d-block"></i>No image selected' +
                            '</div>' +
                        '</div>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm w-100 mt-2 pick-gallery-image"><i class="ri-upload-2-line me-1"></i>Change Image</button>' +
                        '<input type="hidden" name="gallery_items[' + index + '][id]" class="gallery-item-id" value="' + escapeHtml(data.id || '') + '">' +
                        '<input type="hidden" name="gallery_items[' + index + '][media_id]" class="gallery-media-id" value="' + escapeHtml(data.media_id || '') + '">' +
                    '</div>' +
                    '<div class="col-md-9">' +
                        '<div class="row g-2">' +
                            '<div class="col-md-6"><label class="form-label small">Image Title</label>' +
                                '<input type="text" class="form-control form-control-sm" name="gallery_items[' + index + '][title]" value="' + escapeHtml(data.title || '') + '"></div>' +
                            '<div class="col-md-6"><label class="form-label small">ALT Text</label>' +
                                '<input type="text" class="form-control form-control-sm" name="gallery_items[' + index + '][alt_text]" value="' + escapeHtml(data.alt_text || '') + '"></div>' +
                            '<div class="col-12"><label class="form-label small">Caption</label>' +
                                '<textarea class="form-control form-control-sm" rows="2" name="gallery_items[' + index + '][caption]">' + escapeHtml(data.caption || '') + '</textarea></div>' +
                            '<div class="col-md-6"><label class="form-label small">SEO Title</label>' +
                                '<input type="text" class="form-control form-control-sm" name="gallery_items[' + index + '][seo_title]" value="' + escapeHtml(data.seo_title || '') + '"></div>' +
                            '<div class="col-md-6"><label class="form-label small">Tags</label>' +
                                '<input type="text" class="form-control form-control-sm" name="gallery_items[' + index + '][image_tags]" placeholder="Comma separated" value="' + escapeHtml(data.image_tags || '') + '"></div>' +
                            '<div class="col-12"><label class="form-label small">SEO Description</label>' +
                                '<textarea class="form-control form-control-sm" rows="2" name="gallery_items[' + index + '][seo_description]">' + escapeHtml(data.seo_description || '') + '</textarea></div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function reindexItems() {
        if (!galleryContainer) return;
        var items = galleryContainer.querySelectorAll('.gallery-item-container');
        items.forEach(function (item, index) {
            item.setAttribute('data-index', index);
            var badge = item.querySelector('.badge');
            if (badge) badge.innerHTML = '<i class="ri-image-line me-1"></i>Slide ' + (index + 1);
            item.querySelectorAll('[name^="gallery_items"]').forEach(function (input) {
                input.name = input.name.replace(/gallery_items\[\d+\]/, 'gallery_items[' + index + ']');
            });
        });
    }

    function moveItem(item, direction) {
        if (!galleryContainer || !item) return;
        var sibling = direction === 'up' ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (direction === 'up') {
            galleryContainer.insertBefore(item, sibling);
        } else {
            galleryContainer.insertBefore(sibling, item);
        }
        reindexItems();
    }

    function openGalleryMediaPicker(options) {
        options = options || {};
        if (typeof openMediaLibrary !== 'function') return;

        openMediaLibrary('galleryItemMediaModal', {
            title: options.multiple ? 'Select Gallery Images' : 'Select Gallery Image',
            setButtonText: options.multiple ? 'Add Selected Images' : 'Use This Image',
            forcedType: 'image',
            apiMode: 'common',
            multiple: !!options.multiple,
            onImageSelected: options.multiple ? null : function (media) {
                var slideData = mapMediaToSlideData(media);
                if (activePickerContainer) {
                    applyMediaToSlide(activePickerContainer, slideData);
                } else {
                    addGalleryItem(slideData);
                }
            },
            onImagesSelected: options.multiple ? function (items) {
                if (!items || !items.length) return;
                items.forEach(function (media) {
                    addGalleryItem(mapMediaToSlideData(media));
                });
            } : null,
        });
    }

    function bindItemEvents(item) {
        item.querySelector('.remove-gallery-item')?.addEventListener('click', function () {
            if (confirm('Remove this gallery slide?')) {
                item.remove();
                reindexItems();
                toggleEmptyState();
            }
        });

        item.querySelector('.move-gallery-up')?.addEventListener('click', function () {
            moveItem(item, 'up');
        });

        item.querySelector('.move-gallery-down')?.addEventListener('click', function () {
            moveItem(item, 'down');
        });

        item.querySelector('.pick-gallery-image')?.addEventListener('click', function () {
            activePickerContainer = item;
            openGalleryMediaPicker({ multiple: false });
        });
    }

    function addGalleryItem(data) {
        if (!galleryContainer) return;
        var index = galleryContainer.querySelectorAll('.gallery-item-container').length;
        var wrapper = document.createElement('div');
        wrapper.innerHTML = buildItemHtml(index, data);
        var item = wrapper.firstElementChild;
        galleryContainer.appendChild(item);
        bindItemEvents(item);
        toggleEmptyState();
        return item;
    }

    function init() {
        galleryContainer = document.getElementById('imageGallery');
        noImagesMessage = document.getElementById('noGalleryImages');
        if (!galleryContainer) return;

        var initial = window.__galleryItemsInitial || [];
        if (initial.length) {
            initial.forEach(function (item) { addGalleryItem(item); });
        } else {
            toggleEmptyState();
        }

        document.getElementById('addGalleryImage')?.addEventListener('click', function () {
            activePickerContainer = null;
            openGalleryMediaPicker({ multiple: true });
        });
    }

    return { init: init, addGalleryItem: addGalleryItem, mapMediaToSlideData: mapMediaToSlideData };
})();
