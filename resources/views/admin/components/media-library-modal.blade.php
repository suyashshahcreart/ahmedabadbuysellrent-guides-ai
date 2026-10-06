@php
    $mediaTypes = $mediaTypes ?? \App\Support\MediaLibraryUi::typeGroups();
    $dates = $dates ?? ($mediaDates ?? \App\Support\MediaLibraryUi::dateOptions());
@endphp
<!-- Media Library Modal -->
<div class="modal fade media-library-modal-root"
     id="{{ $modalId ?? 'mediaLibraryModal' }}"
     tabindex="-1"
     aria-labelledby="{{ ($modalId ?? 'mediaLibraryModal') }}Label"
     aria-hidden="true"
     data-media-list-url="{{ route('administrator.media.json') }}"
     data-media-upload-url="{{ route('administrator.media.store') }}">
    <div class="modal-dialog media-library-modal-dialog modal-fullscreen-sm-down modal-dialog-centered">
        <div class="modal-content media-library-modal-content d-flex flex-column h-100 min-h-0 border-0 shadow rounded-2">
            <div class="modal-header media-library-modal-header border-0 border-bottom flex-shrink-0 py-3 px-4">
                <div class="d-flex flex-column min-w-0 me-2">
                    <h5 class="modal-title text-truncate d-flex align-items-center gap-1 mb-0" id="{{ ($modalId ?? 'mediaLibraryModal') }}Label">
                        <i class="ri-image-2-line text-primary flex-shrink-0" aria-hidden="true"></i>
                        <span class="text-truncate" id="{{ ($modalId ?? 'mediaLibraryModal') }}LabelText">{{ $modalTitle ?? 'Select Image' }}</span>
                    </h5>
                    <p class="text-muted small mb-0 d-none d-md-block">Pick an image, upload a new file, and confirm with the action below.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 d-flex flex-column flex-grow-1 min-h-0 media-library-modal-body overflow-hidden">
                <!-- Tabs: scroll together with content below; header + footer of modal stay on screen (see .media-library-body-scroll) -->
                <ul class="nav nav-tabs flex-shrink-0 px-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-2" id="media-library-tab-{{ $modalId ?? 'mediaLibraryModal' }}" data-bs-toggle="tab" data-bs-target="#media-library-content-{{ $modalId ?? 'mediaLibraryModal' }}" type="button" role="tab" aria-selected="true" aria-current="page">
                            <i class="ri-folders-line me-1"></i>Media library
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2" id="upload-tab-{{ $modalId ?? 'mediaLibraryModal' }}" data-bs-toggle="tab" data-bs-target="#upload-content-{{ $modalId ?? 'mediaLibraryModal' }}" type="button" role="tab" tabindex="-1">
                            <i class="ri-upload-cloud-2-line me-1"></i>Upload files
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content w-100 media-library-tab-content">
                    <!-- Media library tab: first in DOM; no d-flex on .tab-pane (d-flex !important breaks inactive display:none) -->
                    <div class="tab-pane fade show active p-0 media-library-tab--browse" id="media-library-content-{{ $modalId ?? 'mediaLibraryModal' }}" role="tabpanel" aria-labelledby="media-library-tab-{{ $modalId ?? 'mediaLibraryModal' }}">
                        <div class="media-library-tab-inner d-flex flex-column flex-lg-row flex-fill min-h-0 w-100 h-100">
                            <!-- Media Grid -->
                            <div class="media-grid p-3 min-h-0 d-flex flex-column media-library-grid-main" style="min-width: 0;">
                                <!-- Filter Bar -->
                                <div class="media-filters mb-3 flex-shrink-0">
                                    <div class="row g-2 align-items-center">
                                        <div class="col col-md-4 col-lg-3">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-search-line"></i>
                                                </span>
                                                <input type="text" class="form-control" id="mediaLibrarySearch-{{ $modalId ?? 'mediaLibraryModal' }}" placeholder="Search media...">
                                            </div>
                                        </div>
                                        <div class="col-auto align-self-center px-0" id="ml-forced-badge-{{ $modalId ?? 'mediaLibraryModal' }}" style="min-width:0;"></div>
                                        <div class="col-6 col-md-3 col-lg-2">
                                            <select class="form-select" id="mediaLibraryTypeFilter-{{ $modalId ?? 'mediaLibraryModal' }}">
                                                <option value="">All Types</option>
                                                @isset($mediaTypes)
                                                    @foreach($mediaTypes as $group => $types)
                                                        <optgroup label="{{ ucfirst($group) }}">
                                                            @foreach($types as $type)
                                                                <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                @endisset
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-3 col-lg-2">
                                            <select class="form-select" id="mediaLibraryDateFilter-{{ $modalId ?? 'mediaLibraryModal' }}">
                                                <option value="">All dates</option>
                                                @isset($dates)
                                                    <optgroup label="Date Ranges">
                                                        @foreach($dates as $value => $label)
                                                            @if(in_array($value, ['today', 'last7days', 'last30days', 'thismonth', 'lastmonth']))
                                                                <option value="{{ $value }}">{{ $label }}</option>
                                                            @endif
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Specific Months">
                                                        @foreach($dates as $value => $label)
                                                            @if(!in_array($value, ['today', 'last7days', 'last30days', 'thismonth', 'lastmonth']))
                                                                <option value="{{ $value }}">{{ $label }}</option>
                                                            @endif
                                                        @endforeach
                                                    </optgroup>
                                                @endisset
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-2 col-lg-2">
                                            <select class="form-select" id="mediaLibrarySortFilter-{{ $modalId ?? 'mediaLibraryModal' }}">
                                                <option value="newest">Newest First</option>
                                                <option value="oldest">Oldest First</option>
                                                <option value="name">Name A-Z</option>
                                                <option value="name_desc">Name Z-A</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-library-items-scroll">
                                    <div class="media-items w-100" id="mediaItems-{{ $modalId ?? 'mediaLibraryModal' }}">
                                        <!-- Items loaded dynamically -->
                                    </div>
                                    <div class="media-load-more-wrap text-center py-2" id="mediaPagination-{{ $modalId ?? 'mediaLibraryModal' }}"></div>
                                    <div class="text-center py-5 d-none" id="mediaLoading-{{ $modalId ?? 'mediaLibraryModal' }}">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Attachment Details Sidebar -->
                            <div class="attachment-details w-100 border-top border-lg-0 border-lg-start border-light-subtle bg-body min-h-0 d-flex flex-column media-library-attachment-col">
                                <div class="p-2 p-lg-3 overflow-y-auto small media-library-attachment-body">
                                    <h6 class="border-bottom pb-1 text-uppercase text-muted small mb-2">Attachment details</h6>

                                    {{-- Preview image / file icon (shown when item is selected) --}}
                                    <div id="mediaPreviewWrap-{{ $modalId ?? 'mediaLibraryModal' }}" class="media-preview-wrap d-none">
                                        <img id="mediaPreviewImg-{{ $modalId ?? 'mediaLibraryModal' }}" src="" alt="" class="d-none">
                                        <div id="mediaPreviewIcon-{{ $modalId ?? 'mediaLibraryModal' }}" class="media-preview-placeholder d-none">
                                            <i class="fs-1 text-muted" id="mediaPreviewIconEl-{{ $modalId ?? 'mediaLibraryModal' }}"></i>
                                            <span class="small text-muted" id="mediaPreviewIconType-{{ $modalId ?? 'mediaLibraryModal' }}"></span>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-xs mt-1 d-none" id="mlBtnViewDoc-{{ $modalId ?? 'mediaLibraryModal' }}" style="font-size:.75rem;padding:.2rem .5rem;">
                                            <i class="ri-eye-line me-1"></i>View
                                        </button>
                                    </div>

                                    {{-- 360° compact summary — full editor opens in sub-modal --}}
                                    <div id="ml360Compact-{{ $modalId ?? 'mediaLibraryModal' }}" class="ml-360-compact mb-2 d-none">
                                        <div class="d-flex align-items-start gap-2">
                                            <img id="ml360CompactThumb-{{ $modalId ?? 'mediaLibraryModal' }}" src="" alt="360° thumbnail" class="rounded border flex-shrink-0 ml-360-compact-thumb">
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="d-flex align-items-center gap-1 mb-1">
                                                    <span class="badge bg-dark" style="font-size:.6rem;">360°</span>
                                                </div>
                                                <p id="ml360CompactSummary-{{ $modalId ?? 'mediaLibraryModal' }}" class="small text-muted mb-0" style="font-size:.7rem;line-height:1.35;"></p>
                                                <button type="button" class="btn btn-primary btn-sm w-100 mt-2" id="ml360ManageBtn-{{ $modalId ?? 'mediaLibraryModal' }}">
                                                    <i class="ri-vr-line me-1"></i>Manage 360°
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="attachmentDetails-{{ $modalId ?? 'mediaLibraryModal' }}" class="d-none">
                                        {{-- Multi-select: compact read-only label (hidden in single-select) --}}
                                        <p class="text-muted small mb-2 ml-multiselect-hint d-none">
                                            <i class="ri-information-line me-1"></i>Last selected item
                                        </p>

                                        <div id="mediaDetailsForm" class="media-library-attachment-form">
                                            <!-- URL Section — always visible (useful reference in both modes) -->
                                            <div class="mb-2">
                                                <label class="form-label small mb-0">URL</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" id="mediaUrl" readonly>
                                                    <button class="btn btn-primary" type="button" id="copyUrlBtn" title="Copy">
                                                        <i class="ri-clipboard-line"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Title -->
                                            <div class="mb-2 ml-edit-field">
                                                <label for="mediaTitle" class="form-label small mb-0">Title</label>
                                                <input type="text" class="form-control form-control-sm" id="mediaTitle">
                                            </div>

                                            <!-- Alternative Text -->
                                            <div class="mb-2 ml-edit-field">
                                                <label for="mediaAltText" class="form-label small mb-0 d-flex justify-content-between">
                                                    <span>Alt text</span>
                                                    <a href="#" class="text-decoration-none" data-bs-toggle="tooltip"
                                                    title="Describe the purpose of the image. Leave empty if the image is purely decorative.">
                                                        What's this?
                                                    </a>
                                                </label>
                                                <input type="text" class="form-control form-control-sm" id="mediaAltText">
                                            </div>

                                            <!-- Caption -->
                                            <div class="mb-2 ml-edit-field">
                                                <label for="mediaCaption" class="form-label small mb-0">Caption</label>
                                                <textarea class="form-control form-control-sm" id="mediaCaption" rows="1"></textarea>
                                            </div>

                                            <!-- Description -->
                                            <div class="mb-2 ml-edit-field">
                                                <label for="mediaDescription" class="form-label small mb-0">Description</label>
                                                <textarea class="form-control form-control-sm" id="mediaDescription" rows="2"></textarea>
                                            </div>

                                            <!-- File Details — always visible -->
                                            <div class="attachment-info mt-2 pt-2">
                                                <h6 class="border-bottom pb-1 text-uppercase text-muted file-details-title">File details</h6>
                                                <div class="details-list details-list-compact">
                                                    <div class="row mb-1">
                                                        <div class="col-4 text-muted">Uploaded on</div>
                                                        <div class="col-8" id="mediaDate">-</div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="col-4 text-muted">Uploaded by</div>
                                                        <div class="col-8" id="mediaUploader">-</div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="col-4 text-muted">File name</div>
                                                        <div class="col-8" id="mediaFilename">-</div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="col-4 text-muted">File type</div>
                                                        <div class="col-8" id="mediaType">-</div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="col-4 text-muted">File size</div>
                                                        <div class="col-8" id="mediaSize">-</div>
                                                    </div>
                                                    <div class="row mb-1">
                                                        <div class="col-4 text-muted">Dimensions</div>
                                                        <div class="col-8" id="mediaDimensions">-</div>
                                                    </div>
                                                </div>

                                                @can('media_upload')
                                                <div class="attachment-actions mt-2 pt-2 border-top ml-edit-action">
                                                    <button type="button" class="btn btn-primary btn-sm" id="saveMediaDetails">Save</button>
                                                </div>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                    <div id="noAttachmentDetails-{{ $modalId ?? 'mediaLibraryModal' }}" class="media-no-selection py-4">
                                        <i class="ri-image-2-line fs-1 mb-1 opacity-50"></i>
                                        <p class="mb-0 small">Click an item to view details</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload files: second tab; layout lives in .media-library-tab-inner (not on .tab-pane) -->
                    <div class="tab-pane fade p-0 media-library-tab--upload" id="upload-content-{{ $modalId ?? 'mediaLibraryModal' }}" role="tabpanel" aria-labelledby="upload-tab-{{ $modalId ?? 'mediaLibraryModal' }}">
                        <div class="media-library-tab-inner w-100 d-flex flex-column min-h-0 p-0">
                            <div class="p-3 w-100">
                                <div class="upload-area text-center p-5 border rounded" id="dropZone-{{ $modalId ?? 'mediaLibraryModal' }}">
                                    <i class="ri-upload-cloud-2-line display-4 mb-3"></i>
                                    <h5>Drop files to upload</h5>
                                    <p class="text-muted mb-3">or</p>
                                    <button type="button" class="btn btn-primary" id="selectFilesBtn-{{ $modalId ?? 'mediaLibraryModal' }}">
                                        Select Files
                                    </button>
                                    <input type="file" id="fileInput-{{ $modalId ?? 'mediaLibraryModal' }}" class="d-none" multiple
                                      accept=".jpeg,.jpg,.png,.gif,.webp,.avif,.svg,.ico,.mp4,.avi,.webm,.m4v,.mp3,.json,.xml,.sql,.pdf">
                                    <p class="text-muted small mt-3 mb-0">
                                      You can select <strong>multiple files</strong> at once (uploads run one after another so the grid stays in sync). Maximum size 1 GB per file. Allowed types: jpeg, png, jpg, gif, webp, avif, svg, ico, mp4, avi, webm, m4v, mp3, json, xml, sql, pdf.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer media-library-modal-footer border-top flex-shrink-0 py-3 px-4">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="setFeaturedImageBtn-{{ $modalId ?? 'mediaLibraryModal' }}" disabled>
                    <i class="ri-check-line me-1" aria-hidden="true"></i><span class="set-featured-text">{{ $setButtonText ?? 'Select Image' }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 360° manage sub-modal (opened from attachment sidebar) --}}
<div class="modal fade ml-360-manage-modal" id="ml360ManageModal-{{ $modalId ?? 'mediaLibraryModal' }}" tabindex="-1" aria-labelledby="ml360ManageModalLabel-{{ $modalId ?? 'mediaLibraryModal' }}" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable ml-360-manage-dialog">
        <div class="modal-content ml-360-manage-content border-0 shadow-lg">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title mb-0" id="ml360ManageModalLabel-{{ $modalId ?? 'mediaLibraryModal' }}">
                    <i class="ri-vr-line me-1"></i>Manage 360° photo
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="ml360ModalPanoramaViewer-{{ $modalId ?? 'mediaLibraryModal' }}" class="ml-panorama-viewer-modal mb-3" aria-label="360° panorama editor"></div>

                <div id="ml360Panel-{{ $modalId ?? 'mediaLibraryModal' }}" class="ml-360-panel">
                    <p class="text-muted mb-2" style="font-size:.72rem;line-height:1.35;">
                        Click a section below, then drag or zoom the preview — values update automatically.
                    </p>

                    <div class="row g-2 mb-2 ml-360-sections-row">
                        <div class="col-6">
                            <div class="ml-360-section h-100 is-active" id="ml360SectionInit-{{ $modalId ?? 'mediaLibraryModal' }}" data-ml360-mode="init" role="button" tabindex="0">
                                <label class="form-label small fw-semibold mb-1 d-flex align-items-center gap-1">
                                    <i class="ri-compass-3-line"></i>
                                    <span class="text-truncate">Default view (visitor)</span>
                                    <span class="badge bg-primary-subtle text-primary ms-auto flex-shrink-0" style="font-size:.55rem;">edit</span>
                                </label>
                                <div class="mb-1">
                                    <label class="form-label small text-muted mb-0">Yaw <span id="ml360InitYawVal-{{ $modalId ?? 'mediaLibraryModal' }}">0</span>°</label>
                                    <input type="range" class="form-range ml360-slider" data-ml360-field="InitYaw" id="ml360InitYaw-{{ $modalId ?? 'mediaLibraryModal' }}" min="-180" max="180" step="1" value="0">
                                </div>
                                <div class="mb-1">
                                    <label class="form-label small text-muted mb-0">Pitch <span id="ml360InitPitchVal-{{ $modalId ?? 'mediaLibraryModal' }}">0</span>°</label>
                                    <input type="range" class="form-range ml360-slider" data-ml360-field="InitPitch" id="ml360InitPitch-{{ $modalId ?? 'mediaLibraryModal' }}" min="-90" max="90" step="1" value="0">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small text-muted mb-0">Zoom <span id="ml360InitHfovVal-{{ $modalId ?? 'mediaLibraryModal' }}">100</span>°</label>
                                    <input type="range" class="form-range ml360-slider" data-ml360-field="InitHfov" id="ml360InitHfov-{{ $modalId ?? 'mediaLibraryModal' }}" min="50" max="120" step="1" value="100">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="ml-360-section h-100" id="ml360SectionThumb-{{ $modalId ?? 'mediaLibraryModal' }}" data-ml360-mode="thumb" role="button" tabindex="0">
                                <label class="form-label small fw-semibold mb-1 d-flex align-items-center gap-1">
                                    <i class="ri-focus-3-line"></i>
                                    <span class="text-truncate">Thumbnail angle</span>
                                    <span class="badge bg-primary-subtle text-primary ms-auto flex-shrink-0" style="font-size:.55rem;">edit</span>
                                </label>
                                <div class="form-check form-check-sm mb-2">
                                    <input class="form-check-input" type="checkbox" id="ml360ThumbMatchInit-{{ $modalId ?? 'mediaLibraryModal' }}">
                                    <label class="form-check-label small text-muted" for="ml360ThumbMatchInit-{{ $modalId ?? 'mediaLibraryModal' }}">
                                        Same as default view
                                    </label>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label small text-muted mb-0">Yaw <span id="ml360ThumbYawVal-{{ $modalId ?? 'mediaLibraryModal' }}">0</span>°</label>
                                    <input type="range" class="form-range ml360-slider" data-ml360-field="ThumbYaw" id="ml360ThumbYaw-{{ $modalId ?? 'mediaLibraryModal' }}" min="-180" max="180" step="1" value="0">
                                </div>
                                <div class="mb-1">
                                    <label class="form-label small text-muted mb-0">Pitch <span id="ml360ThumbPitchVal-{{ $modalId ?? 'mediaLibraryModal' }}">0</span>°</label>
                                    <input type="range" class="form-range ml360-slider" data-ml360-field="ThumbPitch" id="ml360ThumbPitch-{{ $modalId ?? 'mediaLibraryModal' }}" min="-90" max="90" step="1" value="0">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small text-muted mb-0">Zoom <span id="ml360ThumbHfovVal-{{ $modalId ?? 'mediaLibraryModal' }}">90</span>°</label>
                                    <input type="range" class="form-range ml360-slider" data-ml360-field="ThumbHfov" id="ml360ThumbHfov-{{ $modalId ?? 'mediaLibraryModal' }}" min="50" max="120" step="1" value="90">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-1 flex-wrap mb-2">
                        <button type="button" class="btn btn-soft-secondary btn-sm" id="ml360PreviewBtn-{{ $modalId ?? 'mediaLibraryModal' }}">
                            <i class="ri-image-line me-1"></i>Preview thumb
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="ml360SaveBtn-{{ $modalId ?? 'mediaLibraryModal' }}">
                            <i class="ri-save-line me-1"></i>Save 360°
                        </button>
                    </div>

                    <div id="ml360ThumbPreviewWrap-{{ $modalId ?? 'mediaLibraryModal' }}" class="ml-360-thumb-preview-wrap mt-2">
                        <label class="form-label small text-muted mb-1">Generated thumbnail</label>
                        <div id="ml360ThumbPreviewLoading-{{ $modalId ?? 'mediaLibraryModal' }}" class="d-none text-center text-muted small py-2">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Generating…
                        </div>
                        <p id="ml360ThumbPreviewEmpty-{{ $modalId ?? 'mediaLibraryModal' }}" class="small text-muted mb-2">Save 360° to generate thumbnail.</p>
                        <div class="ml-360-thumb-preview-box">
                            <img id="ml360ThumbPreviewImg-{{ $modalId ?? 'mediaLibraryModal' }}" class="ml-360-thumb-preview-img d-none" alt="360 thumbnail">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ═══════════════════════════════════════════════
   MODAL SIZE
   ═══════════════════════════════════════════════ */
.media-library-modal-root { --ml-h: 98vh; }

.media-library-modal-root .media-library-modal-dialog {
    width: 100%; max-width: 100vw;
    height: var(--ml-h); min-height: 0; max-height: var(--ml-h);
    margin: 0;
}
@media (min-width: 576px) {
    .media-library-modal-root .media-library-modal-dialog {
        width: 88vw; max-width: 88vw;
        margin: 0.5rem auto;
    }
}
.media-library-modal-content { height: 100%; min-height: 0; max-height: 100%; }

/* ═══════════════════════════════════════════════
   FLEX CHAIN — independent scrolling per column
   ═══════════════════════════════════════════════ */
/* Modal body: no overall scroll — each column handles its own */
.media-library-modal-root .modal-body {
    overflow: hidden !important;
}

/* Tab content fills all remaining height */
.media-library-tab-content {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
/* Active tab pane: flex column so inner layout works */
.media-library-tab-content > .tab-pane.show.active {
    display: flex !important;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    width: 100%;
}

/* Inner layout container (grid + attachment columns) */
.media-library-tab-inner {
    width: 100%;
    min-height: 0;
    flex: 1 1 auto;
}
.media-library-tab--browse .media-library-tab-inner {
    min-height: 0;
}

/* Grid column — contains filter bar (pinned) + scrollable area */
.media-library-grid-main {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Scrollable media grid area */
.media-library-items-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    padding-right: 2px; /* thin scrollbar gap */
}
.media-library-items-scroll .media-items {
    padding: 0 0 8px 0;
}

/* Attachment column */
@media (min-width: 992px) {
    .media-library-attachment-col {
        flex: 0 0 280px;
        width: 280px;
        min-width: 220px;
        max-width: 320px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
}
@media (max-width: 991.98px) {
    /* Mobile: attachment panel is a compact strip below the grid */
    .media-library-attachment-col {
        flex: 0 0 auto;
        max-height: 40vh;
    }
    /* On very small screens collapse the form fields so preview stays visible */
    @media (max-height: 600px) {
        .media-library-attachment-col { max-height: 35vh; }
    }
}
/* Attachment scroll body */
.media-library-attachment-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto !important;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}

/* Upload tab: allow scroll (simple content) */
.media-library-tab--upload {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
}

/* ═══════════════════════════════════════════════
   MEDIA GRID
   ═══════════════════════════════════════════════ */
.media-library-modal-root .media-items {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    align-items: start;
    align-content: start;
    justify-items: stretch;
    gap: 8px;
}
@media (max-width: 1199.98px) { .media-library-modal-root .media-items { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 991.98px)  { .media-library-modal-root .media-items { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 575.98px)  { .media-library-modal-root .media-items { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

/* ═══════════════════════════════════════════════
   MEDIA ITEM CARD
   ═══════════════════════════════════════════════ */
.media-library-modal-root .media-item {
    display: block; width: 100%; min-width: 0;
    align-self: start; max-width: 100%; box-sizing: border-box;
}
.media-item {
    position: relative;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    transition: border-color .15s ease, box-shadow .15s ease;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.media-item:hover {
    border-color: #0d6efd;
    box-shadow: 0 3px 8px rgba(0,0,0,.13);
}
.media-item.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.25);
}

/* Checkmark badge */
.media-item .check {
    position: absolute; top: 6px; right: 6px; z-index: 10;
    width: 22px; height: 22px;
    background: #0d6efd; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    opacity: 0; transition: opacity .15s ease;
}
.media-item.selected .check { opacity: 1; }
.media-item .check i { font-size: 13px; line-height: 1; }

/* Image preview */
.media-library-modal-root .media-item .media-item-preview:has(> img) {
    position: relative; display: block; width: 100%;
    height: 130px; overflow: hidden;
    background: var(--bs-tertiary-bg, #f0f0f0); border-radius: 6px;
}
@media (max-width: 991.98px) { .media-library-modal-root .media-item .media-item-preview:has(> img) { height: 140px; } }
@media (max-width: 575.98px) { .media-library-modal-root .media-item .media-item-preview:has(> img) { height: 120px; } }
.media-library-modal-root .media-item .media-item-preview:has(> img) > img {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover; object-position: center; display: block; margin: 0;
}

/* Video preview */
.media-library-modal-root .media-item .media-item-preview:has(> .media-preview-video) {
    position: relative; display: block; width: 100%;
    height: 130px; overflow: hidden; background: #111; border-radius: 6px;
}
.media-library-modal-root .media-item .media-item-preview:has(> .media-preview-video) .media-preview-video {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover; display: block; margin: 0;
}

/* File preview (non-image) */
.file-preview {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-height: 130px; max-height: 200px; padding: 16px; text-align: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px; box-sizing: border-box;
}
.media-library-modal-root .media-item .media-item-preview.file-preview { width: 100%; max-width: 100%; margin: 0; }
.file-icon { margin-bottom: 8px; }
.file-icon i { font-size: 2.2rem; color: #6c757d; }
.file-info-label { font-size: .72rem; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; }
.file-info-type { font-size: .8rem; color: #212529; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }

/* PDF */
.pdf-icon { display: flex; flex-direction: column; align-items: center; }
.pdf-icon i { font-size: 2.2rem; color: #dc3545; }

/* ═══════════════════════════════════════════════
   HOVER OVERLAY — filename + type badge
   ═══════════════════════════════════════════════ */
.media-item-hover-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: linear-gradient(transparent 0%, rgba(0,0,0,.75) 100%);
    color: #fff;
    padding: 20px 7px 5px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 4px;
    opacity: 0;
    transition: opacity .18s ease;
    border-radius: 0 0 6px 6px;
    pointer-events: none;
    z-index: 5;
}
.media-item:hover .media-item-hover-overlay,
.media-item.selected .media-item-hover-overlay { opacity: 1; }

.overlay-filename {
    font-size: .6rem; line-height: 1.25;
    max-width: 72%;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.overlay-type-badge {
    font-size: .52rem; font-weight: 700;
    padding: 1px 4px;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.35);
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .05em;
    border-radius: 3px;
    flex-shrink: 0;
}

/* ═══════════════════════════════════════════════
   FORCED-TYPE INLINE BADGE (next to search)
   ═══════════════════════════════════════════════ */
.ml-inline-forced-badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 3px 9px;
    background: var(--bs-primary-bg-subtle, #cfe2ff);
    color: var(--bs-primary-text-emphasis, #052c65);
    border: 1px solid var(--bs-primary-border-subtle, #9ec5fe);
    border-radius: 20px;
    font-size: .7rem; font-weight: 600;
    white-space: nowrap;
}
/* Hide the old floating badge if it was inserted by older code */
.ml-forced-type-badge { display: none !important; }

/* ═══════════════════════════════════════════════
   LOAD MORE BUTTON
   ═══════════════════════════════════════════════ */
.media-load-more-wrap { padding: 10px 0 4px; text-align: center; }
.media-items-count-hint { font-size: .75rem; color: var(--bs-secondary-color); margin-top: 4px; }

/* ═══════════════════════════════════════════════
   ATTACHMENT PANEL — preview + no-selection
   ═══════════════════════════════════════════════ */
.media-preview-wrap {
    border-radius: 8px; overflow: hidden;
    background: var(--bs-tertiary-bg, #f0f0f0);
    margin-bottom: 10px;
}
.media-preview-wrap img {
    width: 100%; max-height: 180px;
    object-fit: contain; display: block;
}
.media-preview-wrap img.ml-360-sidebar-preview {
    max-height: 100px;
    object-fit: cover;
}
.ml-360-compact {
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 8px;
    background: var(--bs-tertiary-bg, #f8f9fa);
}
.ml-360-compact-thumb {
    width: 72px;
    height: 48px;
    object-fit: cover;
    background: var(--bs-tertiary-bg, #f0f0f0);
}
.ml-panorama-viewer-modal {
    width: 100%;
    height: min(42vh, 360px);
    min-height: 220px;
    border-radius: 8px;
    overflow: hidden;
    background: #111;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
}

/* Nested 360° modal — sits clearly above the media library modal */
.ml-360-manage-modal {
    z-index: 1070 !important;
}
.ml-360-manage-modal.show .ml-360-manage-dialog {
    transform: none;
}
.ml-360-manage-content {
    box-shadow:
        0 0 0 1px rgba(0,0,0,.06),
        0 1.25rem 2.5rem rgba(15,23,42,.22),
        0 .5rem 1rem rgba(15,23,42,.12) !important;
}
.ml-360-manage-modal .modal-header {
    background: linear-gradient(180deg, var(--bs-body-bg, #fff) 0%, var(--bs-tertiary-bg, #f8f9fa) 100%);
    border-bottom: 1px solid var(--bs-border-color);
}
.media-library-modal-root.is-behind-360-modal {
    pointer-events: none;
}
.media-library-modal-root.is-behind-360-modal .modal-dialog {
    filter: brightness(0.62) saturate(0.85);
    transform: scale(0.985);
    transition: filter .2s ease, transform .2s ease;
}
body.ml-360-nested-open .modal-backdrop.ml-360-manage-backdrop {
    z-index: 1065 !important;
    opacity: 0.72 !important;
}
body.ml-360-nested-open .modal-backdrop:not(.ml-360-manage-backdrop).show {
    opacity: 0.35 !important;
}

/* Generated thumbnail — 16:9 (matches server output 800×450) */
.ml-360-thumb-preview-wrap {
    max-width: 360px;
}
.ml-360-thumb-preview-box {
    width: 100%;
    max-width: 320px;
    aspect-ratio: 16 / 9;
    border-radius: 8px;
    overflow: hidden;
    background: var(--bs-tertiary-bg, #f0f0f0);
    border: 1px solid var(--bs-border-color);
}
.ml-360-thumb-preview-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.ml-360-panel {
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 10px;
    background: var(--bs-tertiary-bg, #f8f9fa);
}
.ml-360-panel .form-range { height: .35rem; }
.ml-360-sections-row > [class*="col-"] {
    display: flex;
    flex-direction: column;
}
.ml-360-sections-row .ml-360-section {
    flex: 1 1 auto;
    min-width: 0;
}
.ml-360-section {
    padding: 8px 10px;
    border-radius: 8px;
    border: 2px solid transparent;
    background: var(--bs-body-bg, #fff);
    cursor: pointer;
    transition: border-color .15s ease, background-color .15s ease;
}
.ml-360-section.is-active {
    border-color: var(--bs-primary);
    background: var(--bs-primary-bg-subtle, #e7f1ff);
}
.ml-360-section.is-linked { opacity: 0.72; }
.ml-360-section.is-linked .ml360-slider { pointer-events: none; }
.ml-360-section .ml360-slider { pointer-events: auto; }
@media (max-width: 991.98px) {
    .media-preview-wrap img { max-height: 120px; }
}
.media-preview-placeholder {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 4px; min-height: 80px;
}
.media-no-selection {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 6px; text-align: center;
    color: var(--bs-secondary-color);
}

/* ═══════════════════════════════════════════════
   MOBILE — extra polish
   ═══════════════════════════════════════════════ */
@media (max-width: 575.98px) {
    /* Compact filter row on phones */
    .media-library-modal-root .media-filters .row { row-gap: 6px !important; }
    /* Smaller modal header text */
    .media-library-modal-root .modal-title { font-size: 1rem; }
    /* Upload area less tall */
    .media-library-modal-root .upload-area { padding: 2rem 1rem !important; }
    /* Attachment form fields compact */
    .media-library-attachment-body .form-control { font-size: .8rem; }
    /* Load More button full-width on phone */
    .media-load-more-btn { width: 80%; }
}

/* ═══════════════════════════════════════════════
   DROP ZONE
   ═══════════════════════════════════════════════ */
[id^="dropZone-"] {
    border: 2px dashed #dee2e6;
    transition: border-color .2s ease, background-color .2s ease;
}
[id^="dropZone-"].drag-over {
    border-color: #0d6efd;
    background-color: rgba(13,110,253,.04);
}

/* ═══════════════════════════════════════════════
   ATTACHMENT FORM
   ═══════════════════════════════════════════════ */
.media-library-attachment-form .file-details-title { font-size: .7rem; letter-spacing: .04em; }
.media-library-attachment-form .details-list-compact { font-size: .8rem; }
</style>