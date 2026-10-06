{{--
  Fixed bottom save bar — appears when the primary submit scrolls out of view.

  Variables:
    $formId         — id of the parent form (required for submit association)
    $submitLabel     — button label
    $cancelUrl       — optional cancel link
    $alwaysVisible   — if true, bar is shown on load and stays visible (no scroll observer)
--}}
@php
    $alwaysVisible = (bool) ($alwaysVisible ?? false);
@endphp
<div class="form-sticky-save-bar {{ $alwaysVisible ? '' : 'd-none' }}"
     id="formStickySaveBar"
     aria-hidden="{{ $alwaysVisible ? 'false' : 'true' }}"
     @if($alwaysVisible) data-always-visible="1" @endif>
    <div class="form-sticky-save-bar__inner">
        <div class="d-flex align-items-center justify-content-end gap-2">
            @if(!empty($cancelUrl))
            <a href="{{ $cancelUrl }}" class="btn btn-soft-secondary btn-sm">Cancel</a>
            @endif
            <button type="submit" class="btn btn-primary btn-sm" form="{{ $formId }}">
                <i class="ri-check-line me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>
</div>
