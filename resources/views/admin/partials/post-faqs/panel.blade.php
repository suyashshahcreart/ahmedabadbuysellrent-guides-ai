{{-- Shared inline FAQ panel (create / edit forms) --}}
@php
    $post = $post ?? $faqEntity ?? null;
    $questionPlaceholder = $questionPlaceholder ?? 'e.g. What is the check-in time?';

    $faqs = old('faqs');
    if ($faqs === null && $post) {
        $source = $post->relationLoaded('faqs') ? $post->faqs : $post->faqs()->get();
        $faqs = $source->map(fn ($f) => [
            'id' => $f->id,
            'question' => $f->question,
            'answer' => $f->answer,
            'is_active' => (bool) $f->status,
        ])->all();
    }
    if ($faqs === null) {
        $faqs = [];
    }
    if (empty($faqs)) {
        $faqs = [['question' => '', 'answer' => '', 'is_active' => true]];
    }
@endphp

<div class="card panel-card border-primary border-top mb-3" data-panel-card id="faqs">
    @include('admin.master.partials.panel-card-header-form', [
        'cardTitle'      => 'Frequently Asked Questions',
        'cardSubtitle'   => 'Drag to reorder · collapse each FAQ · active by default',
        'panelCollapsed' => true,
    ])
    <div class="card-body d-none">
        <div id="postFaqsContainer" class="post-faqs-sortable">
            @foreach($faqs as $i => $faq)
                @include('admin.partials.post-faqs.row', [
                    'index'               => $i,
                    'faq'                 => $faq,
                    'expanded'            => $loop->first,
                    'questionPlaceholder' => $questionPlaceholder,
                ])
            @endforeach
        </div>

        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPostFaq">
                <i class="ri-add-line me-1"></i> Add FAQ
            </button>
            <button type="button" class="btn btn-sm btn-soft-secondary" id="btnExpandAllPostFaqs">
                <i class="ri-arrow-down-s-line me-1"></i> Expand All
            </button>
            <button type="button" class="btn btn-sm btn-soft-secondary" id="btnCollapseAllPostFaqs">
                <i class="ri-arrow-up-s-line me-1"></i> Collapse All
            </button>
        </div>

        <div class="mt-2">
            <small class="text-muted">
                <i class="ri-information-line me-1"></i>
                Drag the <i class="ri-drag-move-2-line"></i> handle to reorder. Leave question and answer empty to skip a row.
            </small>
        </div>
    </div>
</div>

@include('admin.partials.faq-editor-assets')

@once
@push('script-bottom')
<script>
(function () {
    function bootPostFaqsPanel() {
        if (typeof initFaqInlinePanel !== 'function') {
            return;
        }

        initFaqInlinePanel({
            containerId: 'postFaqsContainer',
            addBtnId: 'btnAddPostFaq',
            expandAllBtnId: 'btnExpandAllPostFaqs',
            collapseAllBtnId: 'btnCollapseAllPostFaqs',
            questionPlaceholder: @json($questionPlaceholder),
            initialCount: {{ count($faqs) }},
            rowClass: 'post-faq-row',
            sortOrderClass: 'post-faq-sort-order',
            dragHandleClass: 'post-faq-drag-handle',
            headerClass: 'post-faq-header',
            toggleClass: 'post-faq-toggle',
            rowLabelClass: 'post-faq-row-label',
            summaryClass: 'post-faq-summary',
            chevronClass: 'post-faq-chevron',
            bodyClass: 'post-faq-body',
            questionInputClass: 'post-faq-question-input',
            removeBtnClass: 'btn-remove-post-faq',
            bodyIdPrefix: 'post-faq-body',
            editorIdPrefix: 'post-faq-answer',
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootPostFaqsPanel);
    } else {
        bootPostFaqsPanel();
    }
})();
</script>
<style>
.post-faq-drag-handle:active { cursor: grabbing; }
.post-faq-header .post-faq-toggle:hover .post-faq-summary { color: var(--bs-primary); }
.post-faq-row.sortable-ghost { opacity: 0.55; }
.post-faq-row.sortable-chosen { box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,.12); }
.post-faq-chevron { transition: transform .2s ease; display: inline-block; }
.post-faq-chevron.rotated { transform: rotate(180deg); }
</style>
@endpush
@endonce
