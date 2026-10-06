@php
    $popup = $sitePopup ?? [];
    $popupOn = (($popup['system_enabled'] ?? '0') === '1') && (trim((string) ($popup['headline'] ?? '')) !== '' || trim((string) ($popup['body'] ?? '')) !== '');
@endphp
@if($popupOn)
@php
    $overlay = $popup['default_overlay_color'] ?? '#000000';
    $opacity = is_numeric($popup['default_overlay_opacity'] ?? null) ? (float) $popup['default_overlay_opacity'] : 0.5;
    $hex = ltrim((string) $overlay, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2) ?: '00');
    $g = hexdec(substr($hex, 2, 2) ?: '00');
    $b = hexdec(substr($hex, 4, 2) ?: '00');
    $overlayCss = 'rgba('.$r.','.$g.','.$b.','.$opacity.')';
    $width = $popup['default_width'] ?? '600px';
    $delay = (int) ($popup['default_delay_seconds'] ?? 0);
    $maxSession = max(1, (int) ($popup['max_per_session'] ?? 3));
    $ttlDays = max(1, (int) ($popup['visitor_id_ttl_days'] ?? 365));
    $closeOpt = $popup['default_close_option'] ?? 'manual';
    $entry = $popup['default_entry_animation'] ?? 'fadeIn';
    $exit = $popup['default_exit_animation'] ?? 'fadeOut';
@endphp
<div id="guides-site-popup" class="guides-popup" hidden
     data-delay="{{ $delay }}"
     data-max="{{ $maxSession }}"
     data-ttl="{{ $ttlDays }}"
     data-close="{{ $closeOpt }}"
     data-entry="{{ $entry }}"
     data-exit="{{ $exit }}">
    <div class="guides-popup__overlay" style="background: {{ $overlayCss }};"></div>
    <div class="guides-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="guides-popup-title" style="max-width: {{ $width }};">
        @if($closeOpt !== 'auto')
        <button type="button" class="guides-popup__close" data-guides-popup-close aria-label="Close">&times;</button>
        @endif
        @if(!empty($popup['headline']))
        <h2 id="guides-popup-title" class="guides-popup__title">{{ $popup['headline'] }}</h2>
        @endif
        @if(!empty($popup['body']))
        <div class="guides-popup__body">{!! nl2br(e($popup['body'])) !!}</div>
        @endif
        @if(!empty($popup['cta_label']) && !empty($popup['cta_url']))
        <p class="guides-popup__cta">
            <a href="{{ $popup['cta_url'] }}" class="guides-popup__btn">{{ $popup['cta_label'] }}</a>
        </p>
        @endif
    </div>
</div>
<style>
.guides-popup { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1.25rem; }
.guides-popup[hidden] { display: none !important; }
.guides-popup__overlay { position: absolute; inset: 0; }
.guides-popup__dialog { position: relative; background: #fff; color: #1c252e; border-radius: 12px; padding: 1.75rem 1.5rem 1.5rem; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,.28); }
.guides-popup__close { position: absolute; top: .5rem; right: .65rem; border: 0; background: transparent; font-size: 1.75rem; line-height: 1; cursor: pointer; color: #555; }
.guides-popup__title { margin: 0 0 .75rem; font-size: 1.35rem; font-family: "Playfair Display", serif; }
.guides-popup__body { font-size: 1rem; line-height: 1.55; }
.guides-popup__cta { margin: 1.25rem 0 0; }
.guides-popup__btn { display: inline-block; background: #000040; color: #fff; text-decoration: none; padding: .55rem 1.1rem; border-radius: 6px; font-weight: 600; }
.guides-popup.is-in .guides-popup__dialog { animation: guidesPopupIn .35s ease; }
.guides-popup.is-out .guides-popup__dialog { animation: guidesPopupOut .28s ease forwards; }
@keyframes guidesPopupIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
@keyframes guidesPopupOut { from { opacity: 1; } to { opacity: 0; transform: translateY(8px); } }
</style>
<script>
(function () {
    var root = document.getElementById('guides-site-popup');
    if (!root) return;
    var delay = parseInt(root.getAttribute('data-delay') || '0', 10) * 1000;
    var max = parseInt(root.getAttribute('data-max') || '3', 10);
    var ttlDays = parseInt(root.getAttribute('data-ttl') || '365', 10);
    var closeOpt = root.getAttribute('data-close') || 'manual';
    var storageKey = 'guides_popup_shown_at';
    var countKey = 'guides_popup_session_count';
    try {
        var last = localStorage.getItem(storageKey);
        if (last && (Date.now() - parseInt(last, 10)) < ttlDays * 86400000) return;
        var count = parseInt(sessionStorage.getItem(countKey) || '0', 10);
        if (count >= max) return;
    } catch (e) {}
    function openPopup() {
        root.hidden = false;
        root.classList.add('is-in');
        document.body.style.overflow = 'hidden';
        try {
            sessionStorage.setItem(countKey, String(parseInt(sessionStorage.getItem(countKey) || '0', 10) + 1));
            localStorage.setItem(storageKey, String(Date.now()));
        } catch (e) {}
        if (closeOpt === 'auto') {
            setTimeout(closePopup, 8000);
        }
    }
    function closePopup() {
        root.classList.remove('is-in');
        root.classList.add('is-out');
        setTimeout(function () {
            root.hidden = true;
            root.classList.remove('is-out');
            document.body.style.overflow = '';
        }, 280);
    }
    root.querySelectorAll('[data-guides-popup-close], .guides-popup__overlay').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            if (closeOpt === 'auto') return;
            ev.preventDefault();
            closePopup();
        });
    });
    setTimeout(openPopup, Math.max(0, delay));
})();
</script>
@endif
