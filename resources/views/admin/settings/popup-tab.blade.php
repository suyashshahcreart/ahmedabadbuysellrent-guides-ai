<form id="popupSettingsForm" method="POST" action="{{ route('administrator.settings.store') }}">
    @csrf

    <h6 class="mb-3 d-flex align-items-center gap-2 text-primary">
        <i class="ri-window-line"></i> Popup / Modal Global Settings
    </h6>
    <p class="text-muted small mb-3">
        These settings apply globally to all popups unless overridden at the popup level.
    </p>

    <div class="row g-3">

        {{-- System toggle --}}
        <div class="col-12">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch">
                    <input type="hidden"   name="popup_system_enabled" value="0">
                    <input type="checkbox" name="popup_system_enabled" class="form-check-input"
                           id="popupSystemEnabled" value="1"
                           @checked(($popupSettings['system_enabled'] ?? '1') === '1')>
                    <label class="form-check-label fw-medium" for="popupSystemEnabled">Enable popup system</label>
                </div>
                <span class="text-muted small">When off, the public site will not show the popup.</span>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="popupHeadline">Popup headline</label>
            <input type="text" class="form-control" id="popupHeadline" name="popup_headline" maxlength="160"
                   value="{{ $popupSettings['headline'] ?? '' }}" placeholder="Optional site-wide popup title">
        </div>
        <div class="col-12">
            <label class="form-label" for="popupBody">Popup body</label>
            <textarea class="form-control" id="popupBody" name="popup_body" rows="4" maxlength="2000"
                      placeholder="Shown on the public site when the popup system is enabled">{{ $popupSettings['body'] ?? '' }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="popupCtaLabel">CTA label</label>
            <input type="text" class="form-control" id="popupCtaLabel" name="popup_cta_label" maxlength="80"
                   value="{{ $popupSettings['cta_label'] ?? '' }}" placeholder="e.g. Explore guides">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="popupCtaUrl">CTA URL</label>
            <input type="text" class="form-control" id="popupCtaUrl" name="popup_cta_url" maxlength="500"
                   value="{{ $popupSettings['cta_url'] ?? '' }}" placeholder="/guides/">
        </div>

        {{-- Default delay --}}
        <div class="col-md-4">
            <label class="form-label" for="popupDefaultDelay">Default Display Delay (seconds)</label>
            <input type="number" class="form-control" id="popupDefaultDelay"
                   name="popup_default_delay_seconds" min="0" max="60"
                   value="{{ $popupSettings['default_delay_seconds'] ?? '0' }}">
            <p class="form-text">Applied when a popup has no custom delay configured.</p>
        </div>

        {{-- Max per session --}}
        <div class="col-md-4">
            <label class="form-label" for="popupMaxPerSession">Max Popups per Session</label>
            <input type="number" class="form-control" id="popupMaxPerSession"
                   name="popup_max_per_session" min="1" max="50"
                   value="{{ $popupSettings['max_per_session'] ?? '3' }}">
            <p class="form-text">Maximum distinct popups shown to a single visitor per session.</p>
        </div>

        {{-- Visitor ID TTL --}}
        <div class="col-md-4">
            <label class="form-label" for="popupVisitorTtl">Visitor ID TTL (days)</label>
            <input type="number" class="form-control" id="popupVisitorTtl"
                   name="popup_visitor_id_ttl_days" min="1" max="3650"
                   value="{{ $popupSettings['visitor_id_ttl_days'] ?? '365' }}">
            <p class="form-text">How long to remember a visitor for frequency capping.</p>
        </div>

        {{-- Default width --}}
        <div class="col-md-4">
            <label class="form-label" for="popupDefaultWidth">Default Width</label>
            <input type="text" class="form-control" id="popupDefaultWidth"
                   name="popup_default_width" maxlength="32"
                   placeholder="600px" value="{{ $popupSettings['default_width'] ?? '600px' }}">
            <p class="form-text">CSS value: px, %, vw, etc.</p>
        </div>

        {{-- Default overlay color --}}
        <div class="col-md-4">
            <label class="form-label" for="popupDefaultOverlayColor">Default Overlay Color</label>
            <div class="input-group">
                <input type="color" class="form-control form-control-color"
                       value="{{ $popupSettings['default_overlay_color'] ?? '#000000' }}"
                       id="popupDefaultOverlayColorPicker">
                <input type="text" class="form-control" id="popupDefaultOverlayColor"
                       name="popup_default_overlay_color" maxlength="32"
                       value="{{ $popupSettings['default_overlay_color'] ?? '#000000' }}"
                       placeholder="#000000">
            </div>
        </div>

        {{-- Default overlay opacity --}}
        <div class="col-md-4">
            <label class="form-label" for="popupDefaultOverlayOpacity">Default Overlay Opacity</label>
            <input type="number" class="form-control" id="popupDefaultOverlayOpacity"
                   name="popup_default_overlay_opacity" min="0" max="1" step="0.05"
                   value="{{ $popupSettings['default_overlay_opacity'] ?? '0.50' }}">
            <p class="form-text">0 (transparent) to 1 (opaque).</p>
        </div>

        {{-- Default close option --}}
        <div class="col-md-4">
            <label class="form-label" for="popupDefaultCloseOption">Default Close Option</label>
            <select class="form-select" id="popupDefaultCloseOption" name="popup_default_close_option">
                @foreach(['manual' => 'Manual Only', 'auto' => 'Auto Only', 'both' => 'Manual & Auto'] as $val => $lbl)
                <option value="{{ $val }}" @selected(($popupSettings['default_close_option'] ?? 'manual') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        {{-- Default entry animation --}}
        <div class="col-md-4">
            <label class="form-label" for="popupDefaultEntryAnimation">Default Entry Animation</label>
            <input type="text" class="form-control" id="popupDefaultEntryAnimation"
                   name="popup_default_entry_animation" maxlength="64"
                   placeholder="fadeIn" value="{{ $popupSettings['default_entry_animation'] ?? 'fadeIn' }}">
        </div>

        {{-- Default exit animation --}}
        <div class="col-md-4">
            <label class="form-label" for="popupDefaultExitAnimation">Default Exit Animation</label>
            <input type="text" class="form-control" id="popupDefaultExitAnimation"
                   name="popup_default_exit_animation" maxlength="64"
                   placeholder="fadeOut" value="{{ $popupSettings['default_exit_animation'] ?? 'fadeOut' }}">
        </div>

        {{-- Analytics toggle --}}
        <div class="col-12">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch">
                    <input type="hidden"   name="popup_analytics_enabled" value="0">
                    <input type="checkbox" name="popup_analytics_enabled" class="form-check-input"
                           id="popupAnalyticsEnabled" value="1"
                           @checked(($popupSettings['analytics_enabled'] ?? '1') === '1')>
                    <label class="form-check-label fw-medium" for="popupAnalyticsEnabled">Enable analytics tracking</label>
                </div>
                <span class="text-muted small">When off, view/click/close/conversion events are not written.</span>
            </div>
        </div>

    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 justify-content-end mt-4">
        <div id="popupSettingsAlert" class="flex-grow-1 small" role="status" aria-live="polite"></div>
        <button type="submit" class="btn btn-primary">
            <i class="ri-save-3-line me-1"></i> Save popup settings
        </button>
    </div>
</form>
<script>
(function () {
    const picker = document.getElementById('popupDefaultOverlayColorPicker');
    const text   = document.getElementById('popupDefaultOverlayColor');
    if (picker && text) {
        picker.addEventListener('input', () => { text.value = picker.value; });
        text.addEventListener('input', () => { if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value; });
    }
})();
</script>
