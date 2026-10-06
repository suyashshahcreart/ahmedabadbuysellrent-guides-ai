{{--
    Rich Text Editor Component (TinyMCE)
    ─────────────────────────────────────────────────────────────────────────────
    Drop-in reusable component for any form field that needs a rich text editor.

    Required variables:
        $editorId   {string}  Unique HTML id for the <textarea>
        $fieldName  {string}  HTML name attribute (submitted to server)

    Optional variables:
        $value         {string}  Initial HTML content  (default: '')
        $label         {string}  Label text above editor (default: null = no label)
        $required      {bool}    Show red asterisk + required attribute (default: false)
        $height        {int}     Editor height in px  (default: 400)
        $placeholder   {string}  Placeholder text     (default: 'Write content here...')
        $showWordCount {bool}    Show live word count below editor (default: false)
        $minWords      {int}     Word count threshold for warning colour (default: 0)
        $errorKey      {string}  Laravel $errors key  (default: same as $fieldName)
        $mediaModalId  {string}  ID of a media-library-modal for the image toolbar button (default: editorContentMediaModal on all admin pages; pass false to disable)
        $enableIconPicker {bool} Show Insert Icon toolbar button (default: true)

    How to use:
    ───────────
    1. Include the component in your form:

        @include('admin.components.rich-editor', [
            'editorId'      => 'my-editor',
            'fieldName'     => 'content',
            'value'         => old('content', $model?->content),
            'label'         => 'Content',
            'required'      => true,
            'height'        => 450,
            'showWordCount' => true,
            'mediaModalId'  => 'myMediaModal',   // optional
        ])

    2. In your page @section('css'), load TinyMCE BEFORE @vite:

        <script src="{{ asset('admin/js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>

    3. In your page @section('script'), load rich-editor.js and call initRichEditor().
       media-library.js is loaded automatically when the image media-library button is used; include it explicitly if the page uses other pickers too:

        @include('admin.partials.media-library-script')
        <script src="{{ asset('admin/js/rich-editor.js') }}"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            initRichEditor({
                editorId:      'my-editor',
                height:        450,
                showWordCount: true,
                mediaModalId:  'myMediaModal',  // optional; defaults to editorContentMediaModal
                editorType:    'medium',        // optional: full, medium, or small; aliases: editorSize, toolbarMode, variant
            });
        });
        </script>

    4. Optional: include a page-specific modal instead of the shared editorContentMediaModal:

        @include('admin.components.media-library-modal', [
            'modalId'       => 'myMediaModal',
            'modalTitle'    => 'Insert Media',
            'setButtonText' => 'Insert',
            'mediaTypes'    => $mediaTypes,
            'dates'         => $mediaDates,
        ])
--}}
@php
    $editorId      = $editorId      ?? ('rich-editor-' . uniqid());
    $fieldName     = $fieldName     ?? 'content';
    $value         = $value         ?? '';
    $label         = $label         ?? null;
    $required      = $required      ?? false;
    $height        = $height        ?? 400;
    $placeholder   = $placeholder   ?? 'Write content here...';
    $showWordCount = $showWordCount  ?? false;
    $minWords      = $minWords      ?? 0;
    $errorKey      = $errorKey      ?? $fieldName;
    $mediaModalId  = $mediaModalId  ?? null;
    $wordCountElId = $editorId . '-word-count';
@endphp

@if($label)
<label class="form-label fw-semibold">
    {{ $label }}
    @if($required)<span class="text-danger">*</span>@endif
</label>
@endif

{{-- TinyMCE replaces this <textarea>. The name attribute is preserved so the
     HTML content is submitted with the form (TinyMCE syncs on save/submit). --}}
<textarea
    id="{{ $editorId }}"
    name="{{ $fieldName }}"
    {{ $required ? 'required' : '' }}
    class="@error($errorKey) is-invalid @enderror"
    style="visibility:hidden;"
>{{ old($fieldName, $value) }}</textarea>

@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror

@if($showWordCount)
<div class="rich-editor-word-count text-muted small mt-1">
    Word count: <span id="{{ $wordCountElId }}">0</span>
    @if($minWords > 0)
        <span class="text-muted">/ {{ $minWords }} min</span>
    @endif
</div>
@endif
