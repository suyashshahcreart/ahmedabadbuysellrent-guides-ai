@extends('admin.layouts.vertical', ['title' => 'Media Gallery'])

@section('css')
    <link href="{{ asset('admin/css/media-gallery.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/css/media-upload.css') }}">
    <style>
        .upload-dropzone {
            border: 2px dashed #dee2e6;
            border-radius: 5px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-dropzone:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .preview-item {
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
            aspect-ratio: 1;
            z-index: 1;
        }

        .preview-item img,
        .preview-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 1rem;
        }

        .file-icon {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .file-name {
            font-size: 0.8rem;
            text-align: center;
            word-break: break-all;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
            display: inline-block;
            vertical-align: middle;
            cursor: pointer;
        }

        .file-tooltip {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem;
            font-size: 0.8rem;
            text-align: center;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .file-info {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            border-bottom-left-radius: 5px;
        }

        .preview-item:hover .file-tooltip {
            transform: translateY(0);
        }

        .remove-file {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 1;
            z-index: 10;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .preview-item:hover .remove-file {
            opacity: 1;
        }

        .upload-progress {
            display: none;
            margin-top: 1rem;
        }

        .upload-progress .progress {
            height: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
            background-color: #e9ecef;
        }

        .upload-progress .progress-bar {
            background-color: #0d6efd;
            transition: width 0.2s ease;
        }

        .upload-status {
            font-size: 0.875rem;
            color: #6c757d;
            text-align: center;
        }

        .upload-file-list {
            margin-top: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .upload-file-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .upload-file-item .file-name {
            flex-grow: 1;
            margin-right: 1rem;
        }

        .upload-file-item .file-status {
            font-size: 0.875rem;
        }

        .upload-file-item .file-status.success {
            color: #198754;
        }

        .upload-file-item .file-status.error {
            color: #dc3545;
        }

        .upload-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .file-item-error {
            border-left: 3px solid #dc3545;
            padding-left: 0.5rem;
            background-color: rgba(220, 53, 69, 0.05);
        }

        #uploadErrorContainer {
            margin: -1rem -1rem 1rem -1rem;
            border-radius: 0;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .upload-file-item {
            position: relative;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
            background-color: #fff;
            transition: all 0.2s ease;
            min-height: 60px;
        }

        .upload-file-item.error {
            border-color: #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
        }

        .upload-file-item.success {
            border-color: #198754;
            background-color: rgba(25, 135, 84, 0.05);
        }

        .upload-file-item .file-error-message {
            color: #dc3545;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
            text-align: left;
            background: none;
            border: none;
            display: block;
            word-break: break-word;
            white-space: normal;
        }

        .upload-file-item .file-status {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .upload-file-item .progress {
            height: 0.25rem;
            margin-top: 0.5rem;
        }

        /* Media page: grid sits inside theme panel — drop duplicate “card” chrome */
        .admin-media-page .card .media-page-grid,
        .admin-media-page .card .media-grid-container {
            background: transparent;
            box-shadow: none;
            border-radius: 0;
            padding: 0;
            margin: 0 0 0.5rem 0;
        }

        /* Attachment details modal: theme-consistent surface */
        .media-details-modal {
            z-index: 1055;
        }
        .media-details-modal .modal-dialog {
            max-width: 920px;
        }
        .media-details-modal .modal-content {
            /* shadow-lg + rounded-3 on element; keep crisp edge + overflow clip */
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            overflow: hidden;
        }
        /* Darker backdrop when media modals are open (Lahomes panel style) */
        body:has(#mediaDetailsModal.show) .modal-backdrop,
        body:has(#uploadModal.show) .modal-backdrop,
        body:has(#deleteMediaModal.show) .modal-backdrop {
            background: rgba(15, 23, 42, 0.55);
        }
        .media-details-modal .media-details-header {
            background: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color, rgba(0, 0, 0, 0.08));
        }
        /* Preview column: same surface as form + no grey strip */
        .media-details-modal .media-details-preview-col,
        .media-details-modal .media-details-preview-wrap {
            background: var(--bs-body-bg);
        }
        .media-details-modal .media-details-preview-wrap {
            position: relative;
            min-height: 12rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }
        .media-details-modal .media-details-preview {
            position: relative;
            width: 100%;
        }
        .media-details-modal .media-details-preview .preview-container,
        .media-details-modal .media-details-preview {
            min-height: 0;
        }
        /* Undo upload-page .preview-container (grid + scroll) inside attachment modal */
        .media-details-modal .media-details-preview .preview-container {
            display: flex !important;
            max-height: none !important;
            overflow: visible !important;
            grid-template-columns: none;
        }
        .media-details-modal .media-details-preview .preview-container:empty {
            min-height: 12rem;
        }
        .media-details-modal .media-details-preview .preview-container img,
        .media-details-modal .media-details-preview .preview-container video {
            max-width: 100%;
            max-height: min(60vh, 480px);
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .media-details-modal .media-details-preview .preview-container iframe {
            border-radius: var(--bs-border-radius, 0.25rem) !important;
        }
        .media-details-modal .media-details-form {
            background: var(--bs-body-bg);
        }
        .media-details-modal .media-details-meta {
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color, rgba(0, 0, 0, 0.08));
            border-radius: var(--bs-border-radius, 0.25rem);
            padding: 0.75rem 1rem;
        }
        .media-details-modal .media-details-footer {
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color, rgba(0, 0, 0, 0.08));
        }
        .media-details-modal .navigation-buttons .btn-link {
            color: var(--bs-secondary-color);
            font-size: 1.35rem;
            line-height: 1;
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
        }
        .media-details-modal .navigation-buttons .btn-link:hover:not(.disabled) {
            color: var(--bs-primary);
            background: var(--bs-tertiary-bg);
        }
        .media-details-modal .navigation-buttons .btn-link.disabled,
        .media-details-modal .navigation-buttons .btn-link:disabled {
            color: var(--bs-secondary);
            border-color: transparent;
            background: var(--bs-light-bg-subtle, #f8f9fa);
            cursor: not-allowed;
            opacity: 0.5;
        }
        .media-details-modal .navigation-buttons .btn-link.nav-hidden {
            visibility: hidden;
            pointer-events: none;
        }
        .media-details-modal .media-details-copy-btn {
            border-color: var(--bs-border-color);
        }
        .media-details-modal.loading .media-details-preview::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        [data-bs-theme="dark"] .media-details-modal.loading .media-details-preview::after {
            background: rgba(0, 0, 0, 0.45);
        }

        .tooltip {
            font-size: 0.75rem;
        }

        .file-name {
            font-size: 0.8rem;
            text-align: center;
            word-break: break-all;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
            display: inline-block;
            vertical-align: middle;
            cursor: pointer;
        }
        .file-error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
            text-align: left;
            background: #fff0f0;
            border-left: 3px solid #dc3545;
            border-radius: 0.15rem;
            padding: 0.25rem 0.5rem;
            display: block;
            word-break: break-word;
            white-space: normal;
        }
        .error-detail {
            display: block;
            margin-bottom: 2px;
        }
        .upload-file-item.error {
            border-color: #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
        }
        .upload-file-item .remove-file {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 1;
            transition: opacity 0.3s ease;
            z-index: 2;
        }
        .upload-file-item .file-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .upload-file-item {
            position: relative;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
            background-color: #fff;
            transition: all 0.2s ease;
            min-height: 60px;
        }
        .upload-file-item.success {
            border-color: #198754;
            background-color: #f0fff4;
        }
        .card-img-container {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
            min-height: auto;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        /* Media grid: preview fills square cell; hover actions overlay (not side-by-side flex) */
        .admin-media-page .media-grid .media-item .card-img-container {
            display: block;
            aspect-ratio: 1;
            height: auto;
        }
        .admin-media-page .media-grid .media-item .card-img-container .card-body {
            position: absolute;
            inset: 0;
            padding: 0;
            background: transparent;
            pointer-events: none;
            z-index: 2;
        }
        .admin-media-page .media-grid .media-item .card-img-container .action-buttons {
            pointer-events: auto;
        }
        .file-icon, .card-img-container video {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            text-align: center;
        }
        .file-icon i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        .file-icon div, .file-info-label {
            font-size: 0.95rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0.2rem;
            word-break: break-all;
        }
        .file-info-type {
            font-size: 0.8rem;
            color: #adb5bd;
            margin-bottom: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
            display: inline-block;
            vertical-align: middle;
        }
        .card-img-container video {
            max-width: 100%;
            max-height: 100%;
            background: #222;
            border-radius: 8px;
        }
        .file-extension {
            font-size: 0.7rem;
            color: #adb5bd;
            margin-top: 0.2rem;
            font-weight: 500;
        }

        .preview-item.audio .file-preview {
            padding: 0.5rem;
        }

        .preview-item.audio audio {
            width: 100%;
            height: 30px;
            margin-top: 0.5rem;
        }

        .preview-item.json .file-icon {
            color: #f39c12 !important;
        }

        .preview-item.xml .file-icon {
            color: #e74c3c !important;
        }

        .preview-item.sql .file-icon {
            color: #3498db !important;
        }

        .preview-item.audio .file-icon {
            color: #9b59b6 !important;
        }
    </style>
@endsection

@section('content')
    <div class="row admin-media-page">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <nav aria-label="breadcrumb" class="mb-1">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">System</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Media</li>
                        </ol>
                    </nav>
                    <h3 class="mb-0">Media Gallery</h3>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('administrator.dashboard') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
                    @canany(['media.upload','media.manage'])
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal" type="button">
                            <i class="ri-upload-cloud-2-line me-1"></i> Add Media
                        </button>
                    @endcanany
                </div>
            </div>

            <div class="card panel-card border-primary border-top" data-panel-card>
                @include('admin.master.partials.panel-card-header-list', [
                    'cardTitle'    => 'Media library',
                    'cardSubtitle' => 'Search, filter, and manage your uploaded files',
                ])
                <div class="card-body">
        <!-- Media Grid Container -->
        <div class="media-grid-container media-page-grid">
            <!-- Filters -->
            <div class="media-filters mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col col-md-4 col-lg-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search media...">
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-lg-2">
                        <select class="form-select" id="mediaScopeFilter" aria-label="Media source">
                            <option value="all">All media (every file)</option>
                            <option value="library">Media library only (shared bucket)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select class="form-select" id="mediaUsageFilter" aria-label="Reference filter">
                            <option value="">All</option>
                            <option value="referenced">Referenced</option>
                            <option value="unreferenced">Not referenced</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select class="form-select" id="mediaTypeFilter">
                            <option value="">All Types</option>
                            @foreach($mime_types as $group => $types)
                                <optgroup label="{{ ucfirst($group) }}">
                                    @foreach($types as $type)
                                        <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select class="form-select" id="dateFilter" name="date">
                            <option value="">All dates</option>
                            <optgroup label="Date Ranges">
                                <option value="today">Today</option>
                                <option value="last7days">Last 7 days</option>
                                <option value="last30days">Last 30 days</option>
                                <option value="thismonth">This month</option>
                                <option value="lastmonth">Last month</option>
                            </optgroup>
                            <optgroup label="Specific Months">
                                @foreach($dates as $date => $label)
                                    @if(!in_array($date, ['today', 'last7days', 'last30days', 'thismonth', 'lastmonth']))
                                        <option value="{{ $date }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 col-lg-2">
                        <select class="form-select" id="sortFilter">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="media-grid" id="mediaGrid"
                data-can-edit="{{ auth()->user()->can('media.edit') || auth()->user()->can('media.manage') ? 'true' : 'false' }}"
                data-can-delete="{{ auth()->user()->can('media.delete') || auth()->user()->can('media.manage') ? 'true' : 'false' }}"
                data-can-upload="{{ auth()->user()->can('media.upload') || auth()->user()->can('media.manage') ? 'true' : 'false' }}"
            >
                <!-- Media items will be added here dynamically -->
            </div>

            <!-- Pagination / Load more -->
            <div class="pagination-container">
                <div class="media-load-more-wrap text-center py-2" id="mediaLoadMoreWrap" aria-label="Media gallery pagination"></div>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Media Details Modal -->
    <div class="modal fade media-details-modal" id="mediaDetailsModal" tabindex="-1" aria-labelledby="mediaDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg rounded-3">
                <div class="modal-header media-details-header border-bottom border-light-subtle flex-wrap gap-2 py-3 px-4">
                    <h5 class="modal-title d-flex align-items-center gap-2 mb-0" id="mediaDetailsModalLabel">
                        <i class="ri-attachment-2 text-primary" aria-hidden="true"></i>
                        <span>Attachment details</span>
                    </h5>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <div class="navigation-buttons d-flex align-items-center gap-2 me-1">
                        <button type="button" class="btn btn-link p-0 me-0" id="nextMedia" title="Previous media item" aria-label="Previous media item">
                            <i class="ri-arrow-left-s-line"></i>
                            <span class="visually-hidden">Previous media item</span>
                        </button>
                        <button type="button" class="btn btn-link p-0" id="previousMedia" title="Next media item" aria-label="Next media item">
                            <i class="ri-arrow-right-s-line"></i>
                            <span class="visually-hidden">Next media item</span>
                        </button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-7 border-bottom border-md-0 border-md-end border-light-subtle media-details-preview-col bg-body">
                            <div class="p-3 p-md-4 h-100">
                                <p class="text-uppercase text-muted small mb-2 fw-medium">Preview</p>
                                <div class="media-details-preview position-relative">
                                    <div class="media-details-preview-wrap rounded-3 p-2 border border-light-subtle">
                                        <div class="preview-container d-flex align-items-center justify-content-center w-100" style="min-height: 12rem;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 p-3 p-md-4 media-details-form">
                            <p class="text-uppercase text-muted small mb-2 fw-medium">Properties</p>
                            <div class="attachment-details">
                                <form id="mediaDetailsForm" class="mb-0">
                                    <!-- URL Section -->
                                    <div class="mb-3">
                                        <label class="form-label" for="mediaUrl">URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="mediaUrl" readonly>
                                            <button class="btn btn-primary media-details-copy-btn" type="button" id="copyUrlBtn" title="Copy URL">
                                                <i class="ri-clipboard-line"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Title -->
                                    <div class="mb-3">
                                        <label for="mediaTitle" class="form-label">Title</label>
                                        <input type="text" class="form-control" id="mediaTitle" name="title">
                                    </div>

                                    <!-- Alternative Text -->
                                    <div class="mb-3">
                                        <label for="mediaAltText" class="form-label d-flex justify-content-between">
                                            <span>Alternative Text</span>
                                            <a href="#" class="text-decoration-none small" data-bs-toggle="tooltip" 
                                               title="Describe the purpose of the image. Leave empty if the image is purely decorative.">
                                                What's this?
                                            </a>
                                        </label>
                                        <input type="text" class="form-control" id="mediaAltText" name="alt_text">
                                    </div>

                                    <!-- Caption -->
                                    <div class="mb-3">
                                        <label for="mediaCaption" class="form-label">Caption</label>
                                        <textarea class="form-control" id="mediaCaption" name="caption" rows="2"></textarea>
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-3">
                                        <label for="mediaDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="mediaDescription" name="description" rows="3"></textarea>
                                    </div>

                                    

                                    <!-- File Details -->
                                    <div class="attachment-info mt-4">
                                        <h6 class="border-bottom pb-2 text-uppercase small text-muted">File details</h6>
                                        <div class="media-details-meta small">
                                        <div class="details-list mb-0">
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
                                            <div class="row mb-1 align-items-start">
                                                <div class="col-4 text-muted">Used in</div>
                                                <div class="col-8" id="mediaUsages">—</div>
                                            </div>
                                        </div>
                                        </div>

                                        <!-- Delete Action -->
                                         @canany(['media.delete','media.manage'])
                                        <div class="attachment-actions mt-3 pt-3 border-top">
                                            <a href="#" class="text-danger text-decoration-none" id="deleteMediaFile">
                                                Delete permanently
                                            </a>
                                        </div>
                                        @endcanany
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer media-details-footer py-3 px-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnViewDocument" title="Open in Document Viewer" style="display:none">
                        <i class="ri-eye-line me-1"></i>View
                    </button>
                    @canany(['media.edit','media.manage','media.upload'])
                        <button type="button" class="btn btn-primary" id="saveMediaDetails">
                            <i class="ri-save-3-line me-1"></i>Save
                        </button>
                    @endcanany
                </div>
            </div>
        </div>
    </div>
    @canany(['media.upload','media.manage'])
    <!-- Upload Modal -->
    <div class="modal fade media-upload-page-modal" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                    <h5 class="modal-title d-flex align-items-center gap-2 mb-0" id="uploadModalLabel">
                        <i class="ri-upload-cloud-2-line text-primary" aria-hidden="true"></i>Upload media
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <!-- Error Alert Container -->
                    <div id="uploadErrorContainer" class="alert  alert-message alert-danger alert-dismissible fade show mb-3" role="alert" style="display: none;">
                        <div id="uploadErrorMessage"></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <div id="uploadDropzone" class="upload-dropzone">
                        <div class="upload-icon">
                            <i class="ri-upload-cloud-2-line"></i>
                        </div>
                        <p>Drag and drop files here or</p>
                        <input type="file" id="uploadFile" multiple accept=".jpeg,.jpg,.png,.gif,.webp,.avif,.svg,.ico,.mp4,.avi,.webm,.m4v,.mp3,.json,.xml,.sql,.pdf" style="display: none;">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadFile').click()">
                            Select Files
                        </button>
                        <p class="text-muted mt-2">Maximum file size: 1GB. Allowed file types: jpeg, png, jpg, gif, webp, avif, svg, ico, mp4, avi, webm, m4v, mp3, json, xml, sql, pdf</p>
                    </div>

                    <div id="previewContainer" class="preview-container mt-3"></div>

                    <div id="uploadProgress" class="upload-progress mt-3" style="display: none;">
                        <div class="progress">
                            <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div id="uploadStatus" class="upload-status mt-2">Preparing upload...</div>
                        <div id="uploadFileList" class="upload-file-list">
                            <!-- Individual file upload status will be added here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light-subtle py-3 px-4">
                    <button type="button" class="btn btn-light border" id="resetUploadBtn">Reset</button>
                    <button type="button" class="btn btn-primary" id="uploadButton" disabled>
                        <i class="ri-upload-2-line me-1"></i>Upload
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endcanany
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteMediaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-bottom border-light-subtle py-3 px-4">
                    <h5 class="modal-title d-flex align-items-center gap-2 mb-0">
                        <i class="ri-delete-bin-6-line text-danger" aria-hidden="true"></i>Confirm delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="mb-0">Are you sure you want to delete this media file? This action cannot be undone.</p>
                </div>
                <div class="modal-footer border-top border-light-subtle py-3 px-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script-bottom')
    @php
        $galleryScript = public_path('admin/js/media-gallery.js');
        $galleryVersion = is_file($galleryScript) ? (string) filemtime($galleryScript) : '1';
    @endphp
    <script src="{{ asset('admin/js/media-gallery.js') }}?v={{ $galleryVersion }}"></script>
@endsection 