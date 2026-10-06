@php
    $isEdit = (bool) $page->exists;
    $featured = $page->featuredMedia;
    $currentStatus = old('status', $page->status ?? 'draft');
@endphp
@extends('admin.layouts.vertical', ['title' => $isEdit ? 'Edit page' : 'Add New Page'])
@section('css')
<link href="{{ asset('admin/css/post-editor.css') }}" rel="stylesheet">
<link href="{{ asset('admin/css/form-sticky.css') }}" rel="stylesheet">
<script src="{{ asset('admin/js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
@endsection
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('administrator.pages.index') }}">Pages</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add New' }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $isEdit ? ($page->title ?: 'Edit Page') : 'Add New Page' }}</h3>
    </div>
    <a href="{{ route('administrator.pages.index') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
</div>

<form method="post" action="{{ $isEdit ? url('/administrator/pages/'.$page->id) : url('/administrator/pages') }}" id="pageForm" data-auto-slug-from-title="{{ $isEdit ? '0' : '1' }}">
@csrf @if($isEdit) @method('PUT') @endif
<div class="row">
    <div class="col-lg-8">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Content', 'cardSubtitle' => 'Page title, slug, excerpt and body'])
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input class="form-control" name="title" id="title" value="{{ old('title', $page->title) }}" required placeholder="Enter page title...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">URL Slug</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">Slug:</span>
                        <input type="text" id="slug" name="slug_display" class="form-control border-start-0" value="{{ old('slug', $page->slug) }}" placeholder="auto-generated-from-title" disabled>
                        <input type="hidden" name="slug" id="slug_hidden" value="{{ old('slug', $page->slug) }}">
                        <button type="button" class="btn btn-link text-primary text-decoration-none px-2 fw-semibold" id="editSlugBtn"><i class="ri-pencil-line me-1"></i>Edit</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Excerpt</label>
                    <textarea class="form-control" name="excerpt" rows="3" placeholder="Short summary (optional)">{{ old('excerpt', $page->excerpt) }}</textarea>
                </div>
                <div class="mb-0">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label fw-semibold mb-0">Content</label>
                        <button type="button" class="btn btn-sm btn-soft-primary py-0 px-2 js-ask-ai"><i class="ri-magic-line me-1"></i>Ask AI</button>
                    </div>
                    @include('admin.components.rich-editor', [
                        'editorId' => 'page-content-editor',
                        'fieldName' => 'content',
                        'value' => old('content', $page->content),
                        'height' => 450,
                        'placeholder' => 'Write your post content here...',
                        'showWordCount' => true,
                        'mediaModalId' => 'editorContentMediaModal',
                    ])
                </div>
            </div>
        </div>
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'SEO'])
            <div class="card-body">@include('admin.seo._seo_form', ['seoMeta' => $page->seo, 'embeddedMode' => true])</div>
        </div>
        @include('admin.partials.post-faqs.panel', [
            'post' => $page,
            'questionPlaceholder' => 'e.g. What is this page about?',
        ])
    </div>
    <div class="col-lg-4">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Publishing', 'showPanelFullscreen' => false])
            <div class="card-body">
                <label class="form-label fw-semibold small">Status</label>
                <select class="form-select form-select-sm" name="status" id="status">
                    @foreach(['draft','published','scheduled','archived'] as $st)
                        <option value="{{ $st }}" @selected($currentStatus===$st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <div id="publishedAtWrapper" class="mt-3" style="{{ $currentStatus === 'scheduled' ? '' : 'display:none' }}">
                    <label class="form-label fw-semibold small">Publish At</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" value="{{ old('scheduled_at', optional($page->scheduled_at)?->format('Y-m-d\\TH:i')) }}">
                </div>
                <hr class="my-3">
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary flex-fill js-form-primary-submit" type="submit"><i class="ri-check-line me-1"></i> {{ $isEdit ? 'Update Page' : 'Save Page' }}</button>
                    <a href="{{ route('administrator.pages.index') }}" class="btn btn-soft-secondary flex-fill">Cancel</a>
                </div>
            </div>
        </div>
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Featured Image', 'showPanelFullscreen' => false])
            <div class="card-body">
                <input type="hidden" name="featured_media_id" id="featured_media_id" value="{{ old('featured_media_id', $page->featured_media_id) }}">
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
    'formId' => 'pageForm',
    'submitLabel' => $isEdit ? 'Update Page' : 'Save Page',
    'cancelUrl' => route('administrator.pages.index'),
])
@endsection
@section('script-bottom')
<script>
window.__seoPreviewBaseUrl = @json(rtrim(config('app.url'), '/'));
</script>
<script src="{{ asset('admin/js/rich-editor.js') }}"></script>
<script src="{{ asset('admin/js/form-sticky.js') }}"></script>
<script src="{{ asset('admin/js/seo-preview.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initRichEditor === 'function') {
        initRichEditor({
            editorId: 'page-content-editor',
            height: 450,
            placeholder: 'Write your post content here...',
            showWordCount: true,
            mediaModalId: 'editorContentMediaModal',
            editorType: 'full',
            enableIconPicker: false,
        });
    }
    document.getElementById('status')?.addEventListener('change', function () {
        var wrap = document.getElementById('publishedAtWrapper');
        if (wrap) wrap.style.display = this.value === 'scheduled' ? '' : 'none';
    });
    var autoSlugFromTitle = document.getElementById('pageForm')?.getAttribute('data-auto-slug-from-title') !== '0';
    var titleInput = document.getElementById('title');
    var slugDisplay = document.getElementById('slug');
    var slugHidden = document.getElementById('slug_hidden');
    var editSlugBtn = document.getElementById('editSlugBtn');
    var slugEditing = false;
    function generateSlug(text) {
        return text.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-');
    }
    function lockSlug() {
        slugEditing = false;
        slugDisplay.disabled = true;
        slugDisplay.value = generateSlug(slugDisplay.value || '');
        slugHidden.value = slugDisplay.value;
        editSlugBtn.innerHTML = '<i class="ri-pencil-line me-1"></i>Edit';
        editSlugBtn.classList.replace('text-success', 'text-primary');
    }
    function unlockSlug() {
        slugEditing = true;
        slugDisplay.disabled = false;
        slugDisplay.focus();
        editSlugBtn.innerHTML = '<i class="ri-check-line me-1"></i>Done';
        editSlugBtn.classList.replace('text-primary', 'text-success');
    }
    titleInput?.addEventListener('input', function () {
        if (autoSlugFromTitle && !slugEditing) {
            slugDisplay.value = generateSlug(this.value);
            slugHidden.value = slugDisplay.value;
        }
    });
    slugDisplay?.addEventListener('input', function () { slugHidden.value = this.value; });
    slugDisplay?.addEventListener('blur', function () { if (slugEditing) lockSlug(); });
    editSlugBtn?.addEventListener('click', function (e) { e.preventDefault(); slugEditing ? lockSlug() : unlockSlug(); });
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
    document.querySelectorAll('.js-ask-ai').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (typeof showToast === 'function') showToast('AI content generation is not configured on this site.', 'info');
        });
    });
});
</script>
@endsection
