/**
 * SEO Preview — pure client-side preview panels.
 *
 * Renders live Google SERP snippet, Open Graph card, and Twitter card
 * previews that update as the user types in the SEO form.
 *
 * Usage: include this file after the SEO form is rendered.
 * The preview panels are injected into element #seoPreviewPanel.
 */
(function () {
    'use strict';

    /* ── Config ──────────────────────────────────────────────────────────── */
    const GOOGLE_TITLE_WARN  = 60;   // chars — orange warning
    const GOOGLE_DESC_WARN   = 160;  // chars — orange warning
    const DEBOUNCE_MS        = 200;

    /* ── Helpers ─────────────────────────────────────────────────────────── */
    function debounce(fn, delay) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), delay); };
    }

    function escHtml(str) {
        return String(str || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function truncate(str, max) {
        if (!str) return '';
        return str.length > max ? str.slice(0, max - 1) + '…' : str;
    }

    function readField(id) {
        const el = document.getElementById(id);
        return el ? (el.value || '').trim() : '';
    }

    function readSelector(selector) {
        const el = document.querySelector(selector);
        return el ? (el.value || '').trim() : '';
    }

    function hostnameFrom(url) {
        try {
            return new URL(url).hostname;
        } catch (e) {
            return String(url || '').replace(/^https?:\/\//, '').split('/')[0] || window.location.hostname;
        }
    }

    function firstImgSrc(rootId) {
        const root = document.getElementById(rootId);
        if (!root) return null;
        if (root.tagName === 'IMG' && root.src) return root.src;
        const img = root.querySelector('img');
        return img && img.src ? img.src : null;
    }

    /* ── Data collectors ─────────────────────────────────────────────────── */
    function collectData() {
        const pageTitle  = readField('title');
        const excerpt    = readField('excerpt');
        const seoTitle   = readField('seoTitleInput') || pageTitle;
        const seoDesc    = readField('seoDescInput') || excerpt;
        const ogTitle    = readField('ogTitleInput') || seoTitle;
        const ogDesc     = readField('ogDescInput')  || seoDesc;
        const twTitle    = readField('twTitleInput') || ogTitle;
        const twDesc     = readField('twDescInput')  || ogDesc;
        const slug       = readField('seoSlugInput') || readField('slug_hidden') || readField('slug');
        const canonical  = readSelector('[name="canonical_url"]');
        const baseUrl    = window.__seoPreviewBaseUrl || window.location.origin;
        const displayUrl = canonical || (slug ? baseUrl.replace(/\/+$/, '') + '/' + slug.replace(/^\/+/, '') : baseUrl);
        const featuredImg = firstImgSrc('featuredImagePreview');
        let ogImgUrl = firstImgSrc('ogImagePreviewWrap') || firstImgSrc('ogImagePreview') || featuredImg;
        let twImgUrl = firstImgSrc('twImagePreview') || readSelector('[name="twitter_image"]') || ogImgUrl;

        return { seoTitle, seoDesc, ogTitle, ogDesc, twTitle, twDesc, displayUrl, ogImgUrl, twImgUrl };
    }

    /* ── Google SERP preview ─────────────────────────────────────────────── */
    function renderGoogle(d) {
        const title = escHtml(truncate(d.seoTitle || 'Page Title', GOOGLE_TITLE_WARN + 10));
        const desc  = escHtml(truncate(d.seoDesc  || 'Page description will appear here.', GOOGLE_DESC_WARN + 10));
        const url   = escHtml(d.displayUrl || window.location.href);

        const titleWarn  = d.seoTitle && d.seoTitle.length > GOOGLE_TITLE_WARN
            ? ' <span style="color:#f1b44c;font-size:.7rem">⚠ '+d.seoTitle.length+' chars</span>' : '';
        const descWarn   = d.seoDesc && d.seoDesc.length > GOOGLE_DESC_WARN
            ? ' <span style="color:#f1b44c;font-size:.7rem">⚠ '+d.seoDesc.length+' chars</span>' : '';

        return `
<div style="font-family:arial,sans-serif;max-width:580px">
  <div style="font-size:.75rem;color:#006621;margin-bottom:2px">${url}</div>
  <div style="font-size:1.1rem;color:#1a0dab;margin-bottom:4px;cursor:pointer;line-height:1.3">${title}${titleWarn}</div>
  <div style="font-size:.875rem;color:#545454;line-height:1.5">${desc}${descWarn}</div>
</div>`;
    }

    /* ── Open Graph card preview ─────────────────────────────────────────── */
    function renderOg(d) {
        const title = escHtml(truncate(d.ogTitle || 'OG Title', 88));
        const desc  = escHtml(truncate(d.ogDesc  || 'Open Graph description.', 200));
        const url   = escHtml(hostnameFrom(d.displayUrl));

        const imgHtml = d.ogImgUrl
            ? `<img src="${escHtml(d.ogImgUrl)}" style="width:100%;height:150px;object-fit:cover">`
            : `<div style="width:100%;height:150px;background:#e8e8e8;display:flex;align-items:center;justify-content:center;color:#999;font-size:1.5rem">🖼</div>`;

        return `
<div style="font-family:arial,sans-serif;border:1px solid #dddfe2;border-radius:4px;overflow:hidden;max-width:380px">
  ${imgHtml}
  <div style="padding:10px 12px;background:#f2f3f5">
    <div style="font-size:.7rem;color:#606770;text-transform:uppercase;margin-bottom:2px">${url}</div>
    <div style="font-size:.9rem;font-weight:600;color:#1d2129;margin-bottom:3px;line-height:1.3">${title}</div>
    <div style="font-size:.8rem;color:#606770;line-height:1.4">${desc}</div>
  </div>
</div>`;
    }

    /* ── Twitter card preview ────────────────────────────────────────────── */
    function renderTwitter(d) {
        const title = escHtml(truncate(d.twTitle || 'Twitter Card Title', 70));
        const desc  = escHtml(truncate(d.twDesc  || 'Twitter card description.', 125));
        const url   = escHtml(hostnameFrom(d.displayUrl));

        const imgHtml = d.twImgUrl
            ? `<img src="${escHtml(d.twImgUrl)}" style="width:100%;height:140px;object-fit:cover">`
            : `<div style="width:100%;height:140px;background:#cfd9de;display:flex;align-items:center;justify-content:center;color:#8899a6;font-size:1.5rem">🐦</div>`;

        return `
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;border:1px solid #e1e8ed;border-radius:16px;overflow:hidden;max-width:380px">
  ${imgHtml}
  <div style="padding:10px 12px">
    <div style="font-size:.875rem;font-weight:700;color:#14171a;margin-bottom:2px">${title}</div>
    <div style="font-size:.8rem;color:#657786;margin-bottom:4px">${desc}</div>
    <div style="font-size:.75rem;color:#657786">🔗 ${url}</div>
  </div>
</div>`;
    }

    /* ── Render all panels ───────────────────────────────────────────────── */
    function renderAll() {
        const d = collectData();
        const googleEl  = document.getElementById('seoPreviewGoogle');
        const ogEl      = document.getElementById('seoPreviewOg');
        const twitterEl = document.getElementById('seoPreviewTwitter');

        if (googleEl)  googleEl.innerHTML  = renderGoogle(d);
        if (ogEl)      ogEl.innerHTML      = renderOg(d);
        if (twitterEl) twitterEl.innerHTML = renderTwitter(d);
    }

    /* ── Character counters (enhanced) ──────────────────────────────────── */
    function attachCounter(inputId, counterId, recommended) {
        const input   = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        if (!input || !counter) return;

        function update() {
            const len = (input.value || '').length;
            counter.textContent = len + ' / ' + recommended;
            counter.className = 'seo-char-counter ms-1 ' + (
                len === 0      ? '' :
                len <= recommended * 0.8 ? 'ok' :
                len <= recommended      ? 'warn' : 'over'
            );
        }
        input.addEventListener('input', update);
        update();
    }

    /* ── Boot ────────────────────────────────────────────────────────────── */
    function boot() {
        // Attach counters
        attachCounter('seoTitleInput', 'seoTitleCounter', GOOGLE_TITLE_WARN);
        attachCounter('seoDescInput',  'seoDescCounter',  GOOGLE_DESC_WARN);

        // Listen to all relevant inputs for live preview
        const watchIds = ['seoTitleInput','seoDescInput','ogTitleInput','ogDescInput','twTitleInput','twDescInput','seoSlugInput','slug_hidden','slug','title','excerpt'];
        const watchSelectors = ['[name="canonical_url"]','[name="twitter_image"]'];

        const debouncedRender = debounce(renderAll, DEBOUNCE_MS);

        watchIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', debouncedRender);
        });
        watchSelectors.forEach(sel => {
            const el = document.querySelector(sel);
            if (el) el.addEventListener('input', debouncedRender);
        });

        const observer = new MutationObserver(debouncedRender);
        ['ogImagePreview', 'ogImagePreviewWrap', 'twImagePreview', 'featuredImagePreview'].forEach(id => {
            const el = document.getElementById(id);
            if (el) observer.observe(el.parentElement || el, { childList: true, subtree: true, attributes: true });
        });

        // Initial render
        renderAll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
