<div class="panel-actions d-flex gap-2">
    <button type="button" class="btn btn-light border" data-panel-action="collapse" title="Collapse" aria-label="Collapse">
        <i class="{{ ($panelCollapsed ?? false) ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line' }}"></i>
    </button>
    @if($showPanelFullscreen ?? true)
    <button type="button" class="btn btn-light border" data-panel-action="fullscreen" title="Fullscreen" aria-label="Fullscreen">
        <i class="ri-fullscreen-line"></i>
    </button>
    @endif
</div>
