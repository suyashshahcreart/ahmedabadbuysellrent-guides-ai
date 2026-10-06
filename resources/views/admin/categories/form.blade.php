@php
    $isEdit = (bool) $category->exists;
@endphp
@extends('admin.layouts.vertical', ['title' => $isEdit ? 'Edit category' : 'New category'])
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
                <li class="breadcrumb-item"><a href="{{ route('administrator.categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add New' }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $isEdit ? ($category->name ?: 'Edit category') : 'New category' }}</h3>
    </div>
    <a href="{{ route('administrator.categories.index') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
</div>

<form method="post" action="{{ $isEdit ? route('administrator.categories.update', $category) : route('administrator.categories.store') }}" id="categoryForm">
@csrf @if($isEdit) @method('PUT') @endif
<div class="row">
    <div class="col-lg-7">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Category details'])
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $category->name) }}" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="slug_display">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">Slug:</span>
                        <input type="text" id="slug_display" class="form-control" value="{{ old('slug', $category->slug) }}" maxlength="255" placeholder="auto-generated-from-name" disabled>
                        <input type="hidden" name="slug" id="slug_hidden" value="{{ old('slug', $category->slug) }}">
                        <button type="button" class="btn btn-link text-primary text-decoration-none px-2 fw-semibold" id="editSlugBtn"><i class="ri-pencil-line me-1"></i>Edit</button>
                    </div>
                    <div class="form-text">Auto-generated from name. Click Edit to customise.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="nav_label">Nav label</label>
                    <input type="text" class="form-control" name="nav_label" id="nav_label" value="{{ old('nav_label', $category->nav_label) }}" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="parent_id">Parent category</label>
                    <select name="parent_id" id="parent_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($parents as $p)
                            <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="description">Description</label>
                    <textarea class="form-control" name="description" id="description" rows="3">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="sort_order">Sort order</label>
                        <input type="number" class="form-control" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="status">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="published" @selected(old('status', $category->status) === 'published')>Active</option>
                            <option value="draft" @selected(old('status', $category->status) === 'draft')>Inactive</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary mt-3 js-form-primary-submit" type="submit"><i class="ri-check-line me-1"></i>Save category</button>
            </div>
        </div>
        @include('admin.partials.post-faqs.panel', [
            'post' => $category,
            'questionPlaceholder' => 'e.g. What belongs in this category?',
        ])
    </div>
    <div class="col-lg-5">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'SEO'])
            <div class="card-body">@include('admin.seo._seo_form', ['seoMeta' => $category->seo, 'embeddedMode' => true])</div>
        </div>
        @include('admin.components.seo-preview')
    </div>
</div>
@include('admin.components.form-sticky-save-bar', [
    'formId' => 'categoryForm',
    'submitLabel' => 'Save category',
    'cancelUrl' => route('administrator.categories.index'),
])
</form>
@endsection
@section('script-bottom')
<script src="{{ asset('admin/js/form-sticky.js') }}"></script>
<script>
window.__seoPreviewBaseUrl = @json(rtrim(config('app.url'), '/'));
(function () {
    var name = document.getElementById('name');
    var display = document.getElementById('slug_display');
    var hidden = document.getElementById('slug_hidden');
    var editBtn = document.getElementById('editSlugBtn');
    var manual = {{ $isEdit || old('slug') ? 'true' : 'false' }};
    function slugify(v) {
        return String(v || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
    if (name && !manual) {
        name.addEventListener('input', function () {
            if (manual) return;
            var s = slugify(name.value);
            if (display) display.value = s;
            if (hidden) hidden.value = s;
        });
    }
    editBtn?.addEventListener('click', function () {
        manual = true;
        if (display) { display.disabled = false; display.focus(); }
        display?.addEventListener('input', function () { if (hidden) hidden.value = display.value; });
    });
})();
</script>
<script src="{{ asset('admin/js/seo-preview.js') }}"></script>
@endsection
