@php $isEdit = (bool) $insight->exists; @endphp
@extends('admin.layouts.vertical', ['title' => $isEdit ? 'Edit insight' : 'New insight'])
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
                <li class="breadcrumb-item"><a href="/administrator/insights">Insights</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add New' }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $isEdit ? ($insight->title ?: 'Edit insight') : 'New insight' }}</h3>
    </div>
    <a href="/administrator/insights" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
</div>
<form method="post" action="{{ $isEdit ? '/administrator/insights/'.$insight->id : '/administrator/insights' }}" id="insightForm">
@csrf @if($isEdit) @method('PUT') @endif
<div class="row">
    <div class="col-lg-8">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Content'])
            <div class="card-body">
                <div class="mb-3"><label class="form-label fw-semibold">Title <span class="text-danger">*</span></label><input class="form-control" name="title" value="{{ old('title', $insight->title) }}" required></div>
                <div class="mb-3"><label class="form-label fw-semibold">Slug</label><input class="form-control" name="slug" value="{{ old('slug', $insight->slug) }}"></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Stat</label><input class="form-control" name="stat" value="{{ old('stat', $insight->stat) }}"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Label</label><input class="form-control" name="label" value="{{ old('label', $insight->label) }}"></div>
                </div>
                <div class="mb-3"><label class="form-label">Body</label><textarea class="form-control" name="body" rows="3">{{ old('body', $insight->body) }}</textarea></div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Content</label>
                    @include('admin.components.rich-editor', [
                        'editorId' => 'insight-content-editor',
                        'fieldName' => 'content',
                        'value' => old('content', $insight->content),
                        'height' => 360,
                        'placeholder' => 'Write your post content here...',
                        'showWordCount' => true,
                        'mediaModalId' => 'editorContentMediaModal',
                    ])
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card panel-card border-primary border-top mb-3" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Publishing', 'showPanelFullscreen' => false])
            <div class="card-body">
                <label class="form-label fw-semibold small">Status</label>
                <select class="form-select form-select-sm mb-3" name="status">
                    <option value="draft" @selected(old('status', $insight->status)==='draft')>Draft</option>
                    <option value="published" @selected(old('status', $insight->status)==='published')>Published</option>
                </select>
                <button class="btn btn-primary w-100 js-form-primary-submit" type="submit"><i class="ri-check-line me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>
</form>
@include('admin.components.form-sticky-save-bar', [
    'formId' => 'insightForm',
    'submitLabel' => 'Save',
    'cancelUrl' => '/administrator/insights',
])
@endsection
@section('script-bottom')
<script src="{{ asset('admin/js/rich-editor.js') }}"></script>
<script src="{{ asset('admin/js/form-sticky.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initRichEditor === 'function') {
        initRichEditor({
            editorId: 'insight-content-editor',
            height: 360,
            placeholder: 'Write your post content here...',
            showWordCount: true,
            mediaModalId: 'editorContentMediaModal',
            editorType: 'full',
            enableIconPicker: false,
        });
    }
});
</script>
@endsection
