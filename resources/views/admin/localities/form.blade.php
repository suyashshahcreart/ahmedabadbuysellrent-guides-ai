@php
    $isEdit = (bool) $locality->exists;
    $featured = $locality->featuredMedia;
@endphp
@extends('admin.layouts.vertical', ['title' => $isEdit ? 'Edit locality' : 'New locality'])
@section('css')
<link href="{{ asset('admin/css/form-sticky.css') }}" rel="stylesheet">
<script src="{{ asset('admin/js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
@endsection
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="/administrator/localities">Localities</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add New' }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $isEdit ? ($locality->name ?: 'Edit locality') : 'New locality' }}</h3>
    </div>
    <a href="/administrator/localities" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
</div>

<form method="post" action="{{ $isEdit ? '/administrator/localities/'.$locality->id : '/administrator/localities' }}" id="localityForm">
@csrf @if($isEdit) @method('PUT') @endif
<div class="row">
    <div class="col-lg-8">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Content'])
            <div class="card-body">
                <div class="mb-3"><label class="form-label fw-semibold">Name <span class="text-danger">*</span></label><input class="form-control" name="name" value="{{ old('name', $locality->name) }}" required></div>
                <div class="mb-3"><label class="form-label fw-semibold">Slug</label><input class="form-control" name="slug" value="{{ old('slug', $locality->slug) }}"></div>
                <div class="mb-3"><label class="form-label fw-semibold">Short</label><input class="form-control" name="short" value="{{ old('short', $locality->short) }}"></div>
                <div class="mb-3"><label class="form-label fw-semibold">Summary</label><textarea class="form-control" name="summary" rows="3">{{ old('summary', $locality->summary) }}</textarea></div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Overview</label>
                    @include('admin.components.rich-editor', [
                        'editorId' => 'locality-overview-editor',
                        'fieldName' => 'overview',
                        'value' => old('overview', $locality->overview),
                        'height' => 360,
                        'placeholder' => 'Write your post content here...',
                        'showWordCount' => true,
                        'mediaModalId' => 'editorContentMediaModal',
                    ])
                </div>
            </div>
        </div>
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Details', 'panelCollapsed' => true])
            <div class="card-body">
                <div class="mb-3"><label class="form-label">Connectivity</label><textarea class="form-control" name="connectivity" rows="3">{{ old('connectivity', $locality->connectivity) }}</textarea></div>
                <div class="mb-3"><label class="form-label">Schools</label><textarea class="form-control" name="schools" rows="3">{{ old('schools', $locality->schools) }}</textarea></div>
                <div class="mb-3"><label class="form-label">Rental</label><textarea class="form-control" name="rental_information" rows="3">{{ old('rental_information', $locality->rental_information) }}</textarea></div>
                <div class="mb-3"><label class="form-label">Tags (comma)</label><input class="form-control" name="tags" value="{{ old('tags', implode(', ', $locality->tags ?? [])) }}"></div>
                <div class="mb-3"><label class="form-label">Pros (one per line)</label><textarea class="form-control" name="pros" rows="3">{{ old('pros', implode("\n", $locality->pros ?? [])) }}</textarea></div>
                <div class="mb-0"><label class="form-label">Cons (one per line)</label><textarea class="form-control" name="cons" rows="3">{{ old('cons', implode("\n", $locality->cons ?? [])) }}</textarea></div>
            </div>
        </div>
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'SEO'])
            <div class="card-body">@include('admin.seo._seo_form', ['seoMeta' => $locality->seo, 'embeddedMode' => true])</div>
        </div>
        @include('admin.partials.post-faqs.panel', [
            'post' => $locality,
            'questionPlaceholder' => 'e.g. Is this a good area to live?',
        ])
    </div>
    <div class="col-lg-4">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Publishing', 'showPanelFullscreen' => false])
            <div class="card-body">
                <label class="form-label fw-semibold small">Status</label>
                <select class="form-select form-select-sm mb-3" name="status">
                    <option value="draft" @selected(old('status', $locality->status)==='draft')>Draft</option>
                    <option value="published" @selected(old('status', $locality->status)==='published')>Published</option>
                </select>
                <label class="form-label fw-semibold small">Order</label>
                <input class="form-control form-control-sm mb-3" type="number" name="sort_order" value="{{ old('sort_order', $locality->sort_order) }}">
                <button class="btn btn-primary w-100 js-form-primary-submit" type="submit"><i class="ri-check-line me-1"></i> Save</button>
            </div>
        </div>
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Featured Image', 'showPanelFullscreen' => false])
            <div class="card-body">
                <input type="hidden" name="featured_media_id" id="featured_media_id" value="{{ old('featured_media_id', $locality->featured_media_id) }}">
                <div id="featuredImagePreview" class="{{ $featured ? '' : 'd-none' }} mb-2">
                    @if($featured)
                        <img src="{{ $featured->url() }}" class="img-thumbnail w-100" style="max-height:160px;object-fit:cover;" alt="">
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPickFeaturedImage">
                        <i class="ri-image-add-line me-1"></i>
                        <span id="featuredImageBtnLabel">{{ $featured ? 'Change Image' : 'Select Image' }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm {{ $featured ? '' : 'd-none' }}" id="btnRemoveFeaturedImage">
                        <i class="ri-delete-bin-line me-1"></i> Remove
                    </button>
                </div>
            </div>
        </div>
        @include('admin.components.seo-preview')
    </div>
</div>
</form>
@include('admin.components.media-library-modal', [
    'modalId' => 'featuredMediaModal',
    'modalTitle' => 'Select Featured Full Image',
    'setButtonText' => 'Use this image',
])
@include('admin.components.form-sticky-save-bar', [
    'formId' => 'localityForm',
    'submitLabel' => 'Save',
    'cancelUrl' => '/administrator/localities',
])
@endsection
@section('script-bottom')
<script>
window.__seoPreviewBaseUrl = @json(rtrim(config('app.url'), '/').'/areas');
</script>
<script src="{{ asset('admin/js/rich-editor.js') }}"></script>
<script src="{{ asset('admin/js/form-sticky.js') }}"></script>
<script src="{{ asset('admin/js/seo-preview.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initRichEditor === 'function') {
        initRichEditor({
            editorId: 'locality-overview-editor',
            height: 360,
            placeholder: 'Write your post content here...',
            showWordCount: true,
            mediaModalId: 'editorContentMediaModal',
            editorType: 'full',
            enableIconPicker: false,
        });
    }
    document.getElementById('btnPickFeaturedImage')?.addEventListener('click', function () {
        openMediaLibrary('featuredMediaModal', {
            title: 'Select Featured Full Image',
            setButtonText: 'Use this image',
            forcedType: 'image',
            onImageSelected: function (item) {
                document.getElementById('featured_media_id').value = item.id;
                var preview = document.getElementById('featuredImagePreview');
                preview.innerHTML = '<img src="' + item.url + '" class="img-thumbnail w-100" style="max-height:160px;object-fit:cover;" alt="">';
                preview.classList.remove('d-none');
                document.getElementById('btnRemoveFeaturedImage')?.classList.remove('d-none');
                var label = document.getElementById('featuredImageBtnLabel');
                if (label) label.textContent = 'Change Image';
            }
        });
    });
    document.getElementById('btnRemoveFeaturedImage')?.addEventListener('click', function () {
        document.getElementById('featured_media_id').value = '';
        var preview = document.getElementById('featuredImagePreview');
        preview.classList.add('d-none');
        preview.innerHTML = '';
        this.classList.add('d-none');
        var label = document.getElementById('featuredImageBtnLabel');
        if (label) label.textContent = 'Select Image';
    });
});
</script>
@endsection
