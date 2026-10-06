<div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 class="card-title mb-1">{{ $cardTitle }}</h4>
        @if(!empty($cardSubtitle))
            <p class="text-muted mb-0">{{ $cardSubtitle }}</p>
        @endif
    </div>
    @include('admin.master.partials.panel-actions-form', [
        'panelCollapsed'      => $panelCollapsed ?? false,
        'showPanelFullscreen' => $showPanelFullscreen ?? true,
    ])
</div>
