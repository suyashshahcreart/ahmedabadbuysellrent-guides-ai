{{-- Live SEO preview panels — place in sidebar column --}}
<div class="card panel-card border-primary border-top mb-3" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', [
        'cardTitle'      => 'SEO Preview',
        'cardSubtitle'   => 'Live preview as you type',
        'panelCollapsed' => $panelCollapsed ?? false,
    ])
    <div class="card-body {{ ($panelCollapsed ?? false) ? 'd-none' : '' }}">
        <div class="mb-3">
            <div class="d-flex align-items-center gap-1 mb-2">
                <img src="https://www.google.com/favicon.ico" width="14" height="14" alt="Google">
                <span class="text-muted small fw-medium">Google Search</span>
            </div>
            <div id="seoPreviewGoogle" class="small">
                <div class="text-muted">Start typing to see preview…</div>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex align-items-center gap-1 mb-2">
                <i class="ri-facebook-line text-primary"></i>
                <span class="text-muted small fw-medium ms-1">Open Graph</span>
            </div>
            <div id="seoPreviewOg" class="small">
                <div class="text-muted">Fill OG fields to preview…</div>
            </div>
        </div>

        <div>
            <div class="d-flex align-items-center gap-1 mb-2">
                <i class="ri-twitter-x-line"></i>
                <span class="text-muted small fw-medium ms-1">Twitter / X</span>
            </div>
            <div id="seoPreviewTwitter" class="small">
                <div class="text-muted">Fill Twitter fields to preview…</div>
            </div>
        </div>
    </div>
</div>
