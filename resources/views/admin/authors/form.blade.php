@php
    $isEdit = (bool) $author->exists;
@endphp
@extends('admin.layouts.vertical', ['title' => $isEdit ? 'Edit author' : 'New author'])
@section('css')
<link href="{{ asset('admin/css/form-sticky.css') }}" rel="stylesheet">
@endsection
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('administrator.authors.index') }}">Authors</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add New' }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $isEdit ? ($author->name ?: 'Edit author') : 'New author' }}</h3>
    </div>
    <a href="{{ route('administrator.authors.index') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
</div>

<form method="post" action="{{ $isEdit ? route('administrator.authors.update', $author) : route('administrator.authors.store') }}" id="authorForm">
@csrf @if($isEdit) @method('PUT') @endif
<div class="row">
    <div class="col-lg-7">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Author details'])
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $author->name) }}" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="slug_display">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">Slug:</span>
                        <input type="text" id="slug_display" class="form-control" value="{{ old('slug', $author->slug) }}" maxlength="255" disabled>
                        <input type="hidden" name="slug" id="slug_hidden" value="{{ old('slug', $author->slug) }}">
                        <button type="button" class="btn btn-link text-primary text-decoration-none px-2 fw-semibold" id="editSlugBtn"><i class="ri-pencil-line me-1"></i>Edit</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="bio">Bio</label>
                    <textarea class="form-control" name="bio" id="bio" rows="5">{{ old('bio', $author->bio) }}</textarea>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="status" value="0">
                    <input class="form-check-input" type="checkbox" name="status" id="status" value="1" @checked(old('status', $author->status ?? true))>
                    <label class="form-check-label" for="status">Active</label>
                </div>
                <button class="btn btn-primary js-form-primary-submit" type="submit"><i class="ri-check-line me-1"></i>Save author</button>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'SEO'])
            <div class="card-body">@include('admin.seo._seo_form', ['seoMeta' => $author->seo, 'embeddedMode' => true])</div>
        </div>
        @include('admin.components.seo-preview')
    </div>
</div>
@include('admin.components.form-sticky-save-bar', [
    'formId' => 'authorForm',
    'submitLabel' => 'Save author',
    'cancelUrl' => route('administrator.authors.index'),
])
</form>
@endsection
@section('script-bottom')
<script src="{{ asset('admin/js/form-sticky.js') }}"></script>
<script>
window.__seoPreviewBaseUrl = @json(rtrim(config('app.url'), '/').'/authors');
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
