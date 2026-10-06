@php
    $isActive = old('faqs.'.$index.'.is_active', $faq['is_active'] ?? true);
    $bodyId   = 'post-faq-body-' . $index;
    $answerEditorId = 'post-faq-answer-' . $index;
    $questionPlaceholder = $questionPlaceholder ?? 'e.g. What is the check-in time?';
@endphp

<div class="post-faq-row border rounded mb-3 bg-light-subtle overflow-hidden">
    <input type="hidden" class="post-faq-sort-order" name="faqs[{{ $index }}][sort_order]" value="{{ $index }}">
    @if(! empty($faq['id']))
        <input type="hidden" name="faqs[{{ $index }}][id]" value="{{ $faq['id'] }}">
    @endif

    <div class="post-faq-header d-flex align-items-center gap-2 px-3 py-2 border-bottom bg-white">
        <span class="post-faq-drag-handle text-muted" title="Drag to reorder" style="cursor:grab;">
            <i class="ri-drag-move-2-line fs-5"></i>
        </span>

        <button type="button"
                class="btn btn-sm btn-link text-decoration-none text-dark p-0 post-faq-toggle flex-grow-1 text-start d-flex align-items-center gap-1"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $bodyId }}"
                aria-expanded="{{ ($expanded ?? false) ? 'true' : 'false' }}"
                aria-controls="{{ $bodyId }}">
            <span class="badge bg-soft-primary text-primary post-faq-row-label">FAQ {{ $index + 1 }}</span>
            <span class="post-faq-summary text-truncate">{{ filled($faq['question'] ?? '') ? $faq['question'] : 'New FAQ' }}</span>
            <i class="ri-arrow-down-s-line ms-auto post-faq-chevron"></i>
        </button>

        <div class="form-check form-switch mb-0 flex-shrink-0">
            <input class="form-check-input"
                   type="checkbox"
                   name="faqs[{{ $index }}][is_active]"
                   id="faq_active_{{ $index }}"
                   value="1"
                   {{ $isActive ? 'checked' : '' }}>
            <label class="form-check-label small" for="faq_active_{{ $index }}" title="Active (visible on frontend)">Active</label>
        </div>

        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-post-faq flex-shrink-0" title="Remove FAQ">
            <i class="ri-delete-bin-line"></i>
        </button>
    </div>

    <div id="{{ $bodyId }}" class="collapse post-faq-body px-3 py-3 {{ ($expanded ?? false) ? 'show' : '' }}">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Question</label>
            <input type="text"
                   name="faqs[{{ $index }}][question]"
                   value="{{ $faq['question'] ?? '' }}"
                   class="form-control form-control-sm post-faq-question-input @error('faqs.'.$index.'.question') is-invalid @enderror"
                   maxlength="1000"
                   placeholder="{{ $questionPlaceholder }}">
            @error('faqs.'.$index.'.question')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-0">
            <label class="form-label small fw-semibold">Answer</label>
            <textarea id="{{ $answerEditorId }}"
                      name="faqs[{{ $index }}][answer]"
                      rows="3"
                      class="form-control form-control-sm faq-answer-input @error('faqs.'.$index.'.answer') is-invalid @enderror"
                      placeholder="Provide a clear, helpful answer...">{{ $faq['answer'] ?? '' }}</textarea>
            @error('faqs.'.$index.'.answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
