{{-- Shared FAQ answer editor assets (create + edit FAQ sections) --}}
@once
@push('script-bottom')
<script src="{{ asset('admin/js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
<script src="{{ asset('admin/js/rich-editor.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script src="{{ asset('admin/js/faq-inline-panel.js') }}"></script>
<script src="{{ asset('admin/js/faq-section-panel.js') }}"></script>
@endpush
<style>
.faq-row-handle { cursor: grab; }
.faq-row-handle:active { cursor: grabbing; }
.faq-sortable-rows .sortable-ghost { opacity: 0.5; }
.faq-sortable-rows .sortable-chosen { box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,.12); }
.faq-row-chevron { transition: transform .2s ease; display: inline-block; }
.faq-row-chevron.rotated { transform: rotate(180deg); }
.faq-row-header .faq-row-toggle:hover .faq-row-summary { color: var(--bs-primary); }
.faq-answer-input + .tox-tinymce { margin-top: 0.25rem; }
</style>
@endonce
