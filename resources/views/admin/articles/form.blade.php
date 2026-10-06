@php
    $statuses = [
        'draft' => 'Draft',
        'pending_review' => 'Pending review',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'archived' => 'Archived',
    ];
    $currentStatus = old('status', $article->status ?? 'draft');
    $featured = $article->featuredMedia;
    $isEdit = (bool) $article->exists;
@endphp

@extends('admin.layouts.vertical', ['title' => $isEdit ? 'Edit guide' : 'Add New'])

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
                <li class="breadcrumb-item"><a href="{{ route('administrator.articles.index') }}">Guides</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add New' }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $isEdit ? ($article->title ?: 'Edit Guide') : 'Add New Guide' }}</h3>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('administrator.articles.index') }}" class="btn btn-soft-secondary">
            <i class="ri-arrow-go-back-line me-1"></i>Back
        </a>
        @if($isEdit)
        <form method="post" action="{{ url('/administrator/articles/'.$article->id.'/preview') }}">@csrf
            <button class="btn btn-soft-secondary" type="submit">Preview</button>
        </form>
        <form method="post" action="{{ url('/administrator/articles/'.$article->id.'/duplicate') }}">@csrf
            <button class="btn btn-soft-primary" type="submit">Duplicate</button>
        </form>
        @endif
    </div>
</div>

<form method="post"
      action="{{ $isEdit ? url('/administrator/articles/'.$article->id) : route('administrator.articles.store') }}"
      id="articleForm"
      data-auto-slug-from-title="{{ $isEdit ? '0' : '1' }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card panel-card border-primary border-top mb-3" data-panel-card>
                @include('admin.master.partials.panel-card-header-form', [
                    'cardTitle' => 'Content',
                    'cardSubtitle' => 'Title, slug, excerpt and body',
                ])
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="title" class="form-label fw-semibold mb-0">Title <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-soft-primary py-0 px-2 js-ask-ai" data-ai-target="title">
                                <i class="ri-magic-line me-1"></i>Ask AI
                            </button>
                        </div>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $article->title) }}" required maxlength="255"
                               placeholder="Enter guide title...">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label fw-semibold">URL Slug</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">Slug:</span>
                            <input type="text" id="slug" name="slug_display"
                                   class="form-control border-start-0"
                                   value="{{ old('slug', $article->slug) }}" maxlength="255"
                                   placeholder="auto-generated-from-title" disabled>
                            <input type="hidden" name="slug" id="slug_hidden" value="{{ old('slug', $article->slug) }}">
                            <button type="button" class="btn btn-link text-primary text-decoration-none px-2 fw-semibold" id="editSlugBtn">
                                <i class="ri-pencil-line me-1"></i>Edit
                            </button>
                        </div>
                        <div class="form-text">Auto-generated from title. Click <strong>Edit</strong> to customise.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="type" class="form-select">
                            <option value="guide" @selected(old('type',$article->type)==='guide')>Guide</option>
                            <option value="compare" @selected(old('type',$article->type)==='compare')>Compare</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="excerpt" class="form-label fw-semibold">Excerpt</label>
                        <textarea name="excerpt" id="excerpt" rows="3" class="form-control"
                                  placeholder="Short summary (optional)">{{ old('excerpt', $article->excerpt) }}</textarea>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold mb-0">Content</label>
                            <button type="button" class="btn btn-sm btn-soft-primary py-0 px-2 js-ask-ai" data-ai-target="content">
                                <i class="ri-magic-line me-1"></i>Ask AI
                            </button>
                        </div>
                        @include('admin.components.rich-editor', [
                            'editorId' => 'article-content-editor',
                            'fieldName' => 'content',
                            'value' => old('content', $article->content),
                            'height' => 450,
                            'placeholder' => 'Write your post content here...',
                            'showWordCount' => true,
                            'errorKey' => 'content',
                            'mediaModalId' => 'editorContentMediaModal',
                        ])
                    </div>
                </div>
            </div>

            @include('admin.articles._seo_settings')
            @include('admin.articles._faq_panel')
        </div>

        <div class="col-lg-4">
            <div class="card panel-card border-primary border-top mb-3" data-panel-card>
                @include('admin.master.partials.panel-card-header-form', [
                    'cardTitle' => 'Publishing',
                    'cardSubtitle' => 'Status and visibility',
                    'showPanelFullscreen' => false,
                ])
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="publishedAtWrapper" class="mb-3" style="{{ $currentStatus === 'scheduled' ? '' : 'display:none' }}">
                        <label class="form-label fw-semibold small">Publish At</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" value="{{ old('scheduled_at', optional($article->scheduled_at)?->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Published at</label>
                        <input type="datetime-local" name="published_at" class="form-control form-control-sm" value="{{ old('published_at', optional($article->published_at)?->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="featured" value="1" id="featured" @checked(old('featured', $article->featured))>
                        <label class="form-check-label small" for="featured">Featured</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="trending" value="1" id="trending" @checked(old('trending', $article->trending))>
                        <label class="form-check-label small" for="trending">Trending</label>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary flex-fill js-form-primary-submit">
                            <i class="ri-check-line me-1"></i> {{ $isEdit ? 'Update Guide' : 'Save Guide' }}
                        </button>
                        <a href="{{ route('administrator.articles.index') }}" class="btn btn-soft-secondary flex-fill">Cancel</a>
                    </div>
                    @if($isEdit)
                    <button type="button" class="btn btn-outline-danger w-100 mt-2" onclick="document.getElementById('article-trash-form').submit()">Move to Trash</button>
                    @endif
                </div>
            </div>

            <div class="card panel-card border-primary border-top mb-3" data-panel-card>
                @include('admin.master.partials.panel-card-header-form', [
                    'cardTitle' => 'Category & author',
                    'showPanelFullscreen' => false,
                ])
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Category</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('category_id', $article->category_id)==$c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Author</label>
                        <select name="author_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($authors as $a)
                                <option value="{{ $a->id }}" @selected(old('author_id', $article->author_id)==$a->id)>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @include('admin.articles._tags_panel')

            <div class="card panel-card border-primary border-top mb-3" data-panel-card>
                @include('admin.master.partials.panel-card-header-form', [
                    'cardTitle' => 'Featured Image',
                    'cardSubtitle' => 'Select an image from the media library',
                    'showPanelFullscreen' => false,
                ])
                <div class="card-body">
                    <input type="hidden" name="featured_media_id" id="featured_media_id" value="{{ old('featured_media_id', $article->featured_media_id) }}">
                    <div id="featuredImagePreview" class="{{ $featured ? '' : 'd-none' }} mb-2">
                        @if($featured)
                            <img src="{{ $featured->url() }}" class="img-thumbnail w-100" style="max-height:160px;object-fit:cover;" alt="Featured image">
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
@if($isEdit)
<form id="article-trash-form" method="post" action="{{ url('/administrator/articles/'.$article->id) }}" class="d-none">@csrf @method('DELETE')</form>
@endif

@include('admin.components.media-library-modal', [
    'modalId' => 'featuredMediaModal',
    'modalTitle' => 'Select Featured Full Image',
    'setButtonText' => 'Use this image',
])

@include('admin.components.form-sticky-save-bar', [
    'formId' => 'articleForm',
    'submitLabel' => $isEdit ? 'Update Guide' : 'Save Guide',
    'cancelUrl' => route('administrator.articles.index'),
])
@endsection

@section('script-bottom')
<script>
window.__seoPreviewBaseUrl = @json(rtrim(config('app.url'), '/').'/guides');
</script>
<script src="{{ asset('admin/js/rich-editor.js') }}"></script>
<script src="{{ asset('admin/js/form-sticky.js') }}"></script>
<script src="{{ asset('admin/js/seo-preview.js') }}"></script>
<script src="{{ asset('admin/js/blog-taxonomy.js') }}?v={{ @filemtime(public_path('admin/js/blog-taxonomy.js')) ?: 1 }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initRichEditor === 'function') {
        initRichEditor({
            editorId: 'article-content-editor',
            height: 450,
            placeholder: 'Write your post content here...',
            showWordCount: true,
            mediaModalId: 'editorContentMediaModal',
            editorType: 'full',
            enableIconPicker: false,
        });
    }

    document.getElementById('status')?.addEventListener('change', function () {
        document.getElementById('publishedAtWrapper').style.display = this.value === 'scheduled' ? '' : 'none';
    });

    var autoSlugFromTitle = document.getElementById('articleForm')?.getAttribute('data-auto-slug-from-title') !== '0';
    var titleInput   = document.getElementById('title');
    var slugDisplay  = document.getElementById('slug');
    var slugHidden   = document.getElementById('slug_hidden');
    var editSlugBtn  = document.getElementById('editSlugBtn');
    var slugEditing  = false;

    function generateSlug(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }
    function lockSlug() {
        slugEditing = false;
        slugDisplay.disabled = true;
        slugDisplay.value = generateSlug(slugDisplay.value || '');
        slugHidden.value  = slugDisplay.value;
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
            slugHidden.value  = slugDisplay.value;
        }
    });
    slugDisplay?.addEventListener('input', function () {
        slugHidden.value = this.value;
    });
    slugDisplay?.addEventListener('blur', function () {
        if (slugEditing) lockSlug();
    });
    editSlugBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        slugEditing ? lockSlug() : unlockSlug();
    });
    if (autoSlugFromTitle && titleInput && slugDisplay && !slugDisplay.value) {
        slugDisplay.value = generateSlug(titleInput.value || '');
        slugHidden.value  = slugDisplay.value;
    }

    document.getElementById('btnPickFeaturedImage')?.addEventListener('click', function () {
        openMediaLibrary('featuredMediaModal', {
            title: 'Select Featured Full Image',
            setButtonText: 'Use this image',
            forcedType: 'image',
            onImageSelected: function (item) {
                document.getElementById('featured_media_id').value = item.id;
                var preview = document.getElementById('featuredImagePreview');
                preview.innerHTML = '<img src="' + item.url + '" class="img-thumbnail w-100" style="max-height:160px;object-fit:cover;" alt="Featured image">';
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
            if (typeof showToast === 'function') {
                showToast('AI content generation is not configured on this site.', 'info');
            }
        });
    });
});
</script>
@endsection
