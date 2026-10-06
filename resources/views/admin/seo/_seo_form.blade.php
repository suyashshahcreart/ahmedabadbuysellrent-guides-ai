@php
    $robotsOptions = [
        'index, follow' => 'index, follow (default)',
        'noindex, follow' => 'noindex, follow',
        'index, nofollow' => 'index, nofollow',
        'noindex, nofollow' => 'noindex, nofollow',
    ];
    $currentMeta = $seoMeta ?? null;
    $embeddedMode = $embeddedMode ?? true;
    $siteBaseUrl = rtrim($siteBaseUrl ?? config('app.url'), '/');
    $urlPrefix = $urlPrefix ?? null;
    $articleModel = $article ?? null;
    $slugPreview = old('slug', $slugPreview ?? ($articleModel->slug ?? 'your-slug'));
    $isEdit = $isEdit ?? (bool) ($articleModel?->exists);
    $showGuideUrlPreview = $urlPrefix !== null;
    $manageSchemas = (bool) ($manageSchemas ?? false);
    $seoSchemas = $seoSchemas ?? collect();
    $schemaTypes = $schemaTypes ?? \App\Services\SchemaSync::supportedTypes();
@endphp
<style>
.seo-schema-row { background: #f8f9fa; }
.seo-schema-json { font-family: 'SFMono-Regular', Menlo, Consolas, monospace; font-size: .78rem; }
</style>
<style>
.seo-char-counter { font-size: .75rem; }
.seo-char-counter.warn { color: #f1b44c; }
.seo-char-counter.over { color: #f46a6a; }
.seo-char-counter.ok { color: #34c38f; }
.seo-image-placeholder {
    min-width: 180px;
    min-height: 100px;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    color: #878a99;
    background: #f3f6f9;
    border: 1px dashed #ced4da;
    border-radius: .5rem;
    padding: 1rem 1.25rem;
}
</style>

<div class="accordion" id="seoFormAccordion">
    <div class="accordion-item border mb-2">
        <h2 class="accordion-header" id="seoHeadingMeta">
            <button class="accordion-button fw-medium" type="button" data-bs-toggle="collapse"
                    data-bs-target="#seoCollapseMeta" aria-expanded="true" aria-controls="seoCollapseMeta">
                <i class="ri-search-line me-2 text-primary"></i> SEO Meta
            </button>
        </h2>
        <div id="seoCollapseMeta" class="accordion-collapse collapse show" aria-labelledby="seoHeadingMeta">
            <div class="accordion-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">SEO Title
                        <span class="seo-char-counter ms-1" id="seoTitleCounter"></span>
                    </label>
                    <input type="text" class="form-control" id="seoTitleInput" name="seo_title"
                           maxlength="160"
                           value="{{ old('seo_title', $currentMeta?->seo_title) }}"
                           placeholder="Page title for search engines (recommended ≤ 60 characters)">
                    <div class="form-text">Shown as the blue link in Google results.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">SEO Description
                        <span class="seo-char-counter ms-1" id="seoDescCounter"></span>
                    </label>
                    <textarea class="form-control" id="seoDescInput" name="meta_description"
                              rows="3" maxlength="500"
                              placeholder="Meta description shown in Google results (recommended ≤ 160 characters)">{{ old('meta_description', old('seo_description', $currentMeta?->meta_description)) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Keywords</label>
                    <input type="text" class="form-control" name="seo_keywords"
                           value="{{ old('seo_keywords', $currentMeta?->seo_keywords) }}"
                           placeholder="Comma-separated keywords">
                    <div class="form-text">Optional — modern search engines largely ignore meta keywords.</div>
                </div>
                <div class="row">
                    <div class="col-lg-8 mb-3">
                        <label class="form-label fw-medium">Canonical URL</label>
                        <input type="url" class="form-control" name="canonical_url"
                               value="{{ old('canonical_url', $currentMeta?->canonical_url) }}"
                               placeholder="https://example.com/page">
                        <div class="form-text">Leave blank to use the page's own URL.</div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label class="form-label fw-medium">Robots</label>
                        <select class="form-select" name="robots">
                            @foreach($robotsOptions as $val => $label)
                                <option value="{{ $val }}" @selected(old('robots', $currentMeta?->robots ?? 'index, follow') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion-item border mb-2">
        <h2 class="accordion-header" id="seoHeadingOg">
            <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse"
                    data-bs-target="#seoCollapseOg" aria-expanded="false" aria-controls="seoCollapseOg">
                <i class="ri-facebook-line me-2 text-primary"></i> Open Graph (Facebook / LinkedIn)
            </button>
        </h2>
        <div id="seoCollapseOg" class="accordion-collapse collapse" aria-labelledby="seoHeadingOg">
            <div class="accordion-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">OG Title</label>
                    <input type="text" class="form-control" id="ogTitleInput" name="og_title" maxlength="160"
                           value="{{ old('og_title', $currentMeta?->og_title) }}"
                           placeholder="Defaults to SEO title if blank">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">OG Description</label>
                    <textarea class="form-control" id="ogDescInput" name="og_description" rows="2" maxlength="500"
                              placeholder="Defaults to SEO description if blank">{{ old('og_description', $currentMeta?->og_description) }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-medium d-block">OG Image</label>
                    <input type="hidden" name="og_image_media_id" id="ogImageMediaId"
                           value="{{ old('og_image_media_id', $currentMeta?->og_image_media_id) }}">
                    <div id="ogImagePreviewWrap" class="mb-2">
                        @if($currentMeta?->ogImageMedia)
                            <img id="ogImagePreview" src="{{ $currentMeta->ogImageMedia->url() }}"
                                 class="img-fluid rounded" style="max-height:120px;max-width:240px" alt="OG image">
                        @elseif($currentMeta?->og_image)
                            <img id="ogImagePreview" src="{{ $currentMeta->og_image }}"
                                 class="img-fluid rounded" style="max-height:120px;max-width:240px" alt="OG image">
                        @else
                            <div id="ogImagePlaceholder" class="seo-image-placeholder">
                                <i class="ri-image-line fs-3"></i>
                                <span>No OG image</span>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-soft-primary" id="btnPickOg">
                            <i class="ri-image-add-line me-1"></i>
                            <span id="ogImageBtnLabel">{{ ($currentMeta?->ogImageMedia || $currentMeta?->og_image) ? 'Change OG Image' : 'Choose OG Image' }}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft-danger {{ ($currentMeta?->ogImageMedia || $currentMeta?->og_image) ? '' : 'd-none' }}" id="btnRemoveOg">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                    <div class="form-text">Recommended: 1200×630 px, ≤ 8 MB.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion-item border mb-2">
        <h2 class="accordion-header" id="seoHeadingTw">
            <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse"
                    data-bs-target="#seoCollapseTw" aria-expanded="false" aria-controls="seoCollapseTw">
                <i class="ri-twitter-x-line me-2 text-primary"></i> Twitter / X Card
            </button>
        </h2>
        <div id="seoCollapseTw" class="accordion-collapse collapse" aria-labelledby="seoHeadingTw">
            <div class="accordion-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">Twitter Title</label>
                    <input type="text" class="form-control" id="twTitleInput" name="twitter_title" maxlength="160"
                           value="{{ old('twitter_title', $currentMeta?->twitter_title) }}"
                           placeholder="Defaults to OG title / SEO title if blank">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Twitter Description</label>
                    <textarea class="form-control" id="twDescInput" name="twitter_description" rows="2" maxlength="500"
                              placeholder="Defaults to OG description if blank">{{ old('twitter_description', $currentMeta?->twitter_description) }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-medium d-block">Twitter Image</label>
                    <input type="hidden" name="twitter_image" id="twImageUrlInput"
                           value="{{ old('twitter_image', $currentMeta?->twitter_image) }}">
                    <div id="twImagePreviewWrap" class="mb-2">
                        @if($currentMeta?->twitter_image)
                            <img id="twImagePreview" src="{{ $currentMeta->twitter_image }}"
                                 class="img-fluid rounded" style="max-height:120px;max-width:240px" alt="Twitter image">
                        @else
                            <div id="twImagePlaceholder" class="seo-image-placeholder">
                                <i class="ri-image-line fs-3"></i>
                                <span>No Twitter image</span>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-soft-primary" id="btnPickTw">
                            <i class="ri-image-add-line me-1"></i>
                            <span id="twImageBtnLabel">{{ $currentMeta?->twitter_image ? 'Change Twitter Image' : 'Choose Twitter Image' }}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft-danger {{ $currentMeta?->twitter_image ? '' : 'd-none' }}" id="btnRemoveTw">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion-item border mb-2">
        <h2 class="accordion-header" id="seoHeadingUrl">
            <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse"
                    data-bs-target="#seoCollapseUrl" aria-expanded="false" aria-controls="seoCollapseUrl">
                <i class="ri-link me-2 text-primary"></i> URL &amp; Slug
            </button>
        </h2>
        <div id="seoCollapseUrl" class="accordion-collapse collapse" aria-labelledby="seoHeadingUrl">
            <div class="accordion-body">
                @if($showGuideUrlPreview)
                    <p class="text-muted small mb-2">The public URL uses the post slug from the Content section above.</p>
                    <label class="form-label fw-medium d-block">Full URL Preview</label>
                    <code id="seoFullUrlPreview" class="text-muted">{{ $siteBaseUrl }}/{{ $urlPrefix }}/<span id="seoSlugPreview">{{ $slugPreview ?: 'your-slug' }}</span></code>
                @else
                    <p class="text-muted small mb-0">Set the canonical URL above if this page should use a different URL than its default public path.</p>
                @endif
            </div>
        </div>
    </div>

    @if($manageSchemas)
    <div class="accordion-item border">
        <h2 class="accordion-header" id="seoHeadingSchema">
            <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse"
                    data-bs-target="#seoCollapseSchema" aria-expanded="false" aria-controls="seoCollapseSchema">
                <i class="ri-code-box-line me-2 text-primary"></i> Schema JSON-LD
                <span class="badge bg-soft-info text-info ms-2" id="schemaCountBadge">{{ $seoSchemas->count() }}</span>
            </button>
        </h2>
        <div id="seoCollapseSchema" class="accordion-collapse collapse" aria-labelledby="seoHeadingSchema">
            <div class="accordion-body">
                <p class="text-muted small mb-3">
                    Builds Article, BlogPosting, WebPage, BreadcrumbList, FAQPage, and ImageObject from saved title, content, categories, tags, FAQs, SEO meta, and featured image.
                </p>
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-sm btn-info" id="btnGenerateAllSchemas">
                        <i class="ri-brain-line me-1"></i> Auto-Generate All Schemas
                    </button>
                    <select class="form-select form-select-sm" id="generateSchemaType" style="max-width:200px">
                        @foreach($schemaTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-soft-info" id="btnGenerateSchema">
                        <i class="ri-search-eye-line me-1"></i> Generate One
                    </button>
                    <button type="button" class="btn btn-sm btn-primary ms-auto" id="btnAddSchema">
                        <i class="ri-add-line me-1"></i> Add Schema
                    </button>
                </div>

                <div id="seoSchemaList">
                    @foreach($seoSchemas as $schema)
                        @include('admin.seo._schema_row', ['schema' => $schema])
                    @endforeach
                </div>

                <div id="seoSchemaAddForm" class="seo-schema-row p-3 mt-3 border rounded" style="display:none">
                    <h6 class="mb-3"><i class="ri-add-line me-1"></i> New Schema Block</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="newSchemaType">
                                @foreach($schemaTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label form-label-sm">Label / Name</label>
                            <input type="text" class="form-control form-control-sm" id="newSchemaName" placeholder="Optional description">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Sort Order</label>
                            <input type="number" class="form-control form-control-sm" id="newSchemaSortOrder" value="0" min="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">JSON-LD <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-sm seo-schema-json" id="newSchemaJson" rows="8"
                                  placeholder='{&#10;  "@type": "Article"&#10;}'></textarea>
                        <div class="form-text" id="newSchemaJsonError" style="color:#f46a6a;display:none"></div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="newSchemaActive" checked>
                        <label class="form-check-label" for="newSchemaActive">Active</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary" id="btnSubmitNewSchema">
                            <i class="ri-save-line me-1"></i> Save Schema
                        </button>
                        <button type="button" class="btn btn-sm btn-soft-secondary" id="btnCancelNewSchema">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @elseif($showGuideUrlPreview)
    <div class="accordion-item border">
        <h2 class="accordion-header" id="seoHeadingSchema">
            <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse"
                    data-bs-target="#seoCollapseSchema" aria-expanded="false" aria-controls="seoCollapseSchema">
                <i class="ri-code-box-line me-2 text-primary"></i> Schema JSON-LD
            </button>
        </h2>
        <div id="seoCollapseSchema" class="accordion-collapse collapse" aria-labelledby="seoHeadingSchema">
            <div class="accordion-body">
                <div class="alert alert-info mb-0">
                    <i class="ri-information-line me-1"></i>
                    Schema JSON-LD blocks can be added after the post is saved, on the edit page.
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="accordion-item border">
        <h2 class="accordion-header" id="seoHeadingSchema">
            <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse"
                    data-bs-target="#seoCollapseSchema" aria-expanded="false" aria-controls="seoCollapseSchema">
                <i class="ri-code-box-line me-2 text-primary"></i> Schema JSON-LD
            </button>
        </h2>
        <div id="seoCollapseSchema" class="accordion-collapse collapse" aria-labelledby="seoHeadingSchema">
            <div class="accordion-body">
                <div class="alert alert-info mb-0">
                    <i class="ri-information-line me-1"></i>
                    Schema JSON-LD is managed under <a href="{{ route('administrator.seo.schemas.index') }}">SEO → Schemas</a> after this record is saved.
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($manageSchemas)
<div class="modal fade" id="seoSchemaEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="ri-code-box-line me-2"></i> Edit Schema Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editSchemaId">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Type</label>
                        <select class="form-select form-select-sm" id="editSchemaType">
                            @foreach($schemaTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label form-label-sm">Label / Name</label>
                        <input type="text" class="form-control form-control-sm" id="editSchemaName">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Sort Order</label>
                        <input type="number" class="form-control form-control-sm" id="editSchemaSortOrder" min="0">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm">JSON-LD</label>
                    <textarea class="form-control form-control-sm seo-schema-json" id="editSchemaJson" rows="12"></textarea>
                    <div class="form-text" id="editSchemaJsonError" style="color:#f46a6a;display:none"></div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="editSchemaActive">
                    <label class="form-check-label" for="editSchemaActive">Active</label>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-soft-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitEditSchema">
                    <i class="ri-save-line me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@include('admin.components.media-library-modal', [
    'modalId' => 'seoOgMediaModal',
    'modalTitle' => 'Select SEO Image',
    'setButtonText' => 'Use this image',
])

<script>
document.addEventListener('DOMContentLoaded', function () {
    function counter(input, el, rec) {
        if (!input || !el) return;
        const tick = () => {
            const n = (input.value || '').length;
            el.textContent = n + ' / ' + rec;
            el.className = 'seo-char-counter ms-1 ' + (n > rec ? 'over' : (n > rec * 0.9 ? 'warn' : 'ok'));
        };
        input.addEventListener('input', tick);
        tick();
    }
    counter(document.getElementById('seoTitleInput'), document.getElementById('seoTitleCounter'), 60);
    counter(document.getElementById('seoDescInput'), document.getElementById('seoDescCounter'), 160);

    function setImagePreview(wrapId, url, placeholderHtml) {
        var wrap = document.getElementById(wrapId);
        if (!wrap) return;
        if (url) {
            wrap.innerHTML = '<img src="' + url + '" class="img-fluid rounded" style="max-height:120px;max-width:240px" alt="">';
        } else {
            wrap.innerHTML = placeholderHtml;
        }
    }

    var ogPlaceholder = '<div class="seo-image-placeholder"><i class="ri-image-line fs-3"></i><span>No OG image</span></div>';
    var twPlaceholder = '<div class="seo-image-placeholder"><i class="ri-image-line fs-3"></i><span>No Twitter image</span></div>';

    document.getElementById('btnPickOg')?.addEventListener('click', function () {
        if (typeof openMediaLibrary !== 'function') return;
        openMediaLibrary('seoOgMediaModal', {
            title: 'Select OG Image',
            setButtonText: 'Use this image',
            forcedType: 'image',
            onImageSelected: function (item) {
                document.getElementById('ogImageMediaId').value = item.id;
                setImagePreview('ogImagePreviewWrap', item.url, ogPlaceholder);
                document.getElementById('btnRemoveOg')?.classList.remove('d-none');
                var label = document.getElementById('ogImageBtnLabel');
                if (label) label.textContent = 'Change OG Image';
            }
        });
    });
    document.getElementById('btnRemoveOg')?.addEventListener('click', function () {
        document.getElementById('ogImageMediaId').value = '';
        setImagePreview('ogImagePreviewWrap', '', ogPlaceholder);
        this.classList.add('d-none');
        var label = document.getElementById('ogImageBtnLabel');
        if (label) label.textContent = 'Choose OG Image';
    });

    document.getElementById('btnPickTw')?.addEventListener('click', function () {
        if (typeof openMediaLibrary !== 'function') return;
        openMediaLibrary('seoOgMediaModal', {
            title: 'Select Twitter Image',
            setButtonText: 'Use this image',
            forcedType: 'image',
            onImageSelected: function (item) {
                document.getElementById('twImageUrlInput').value = item.url;
                setImagePreview('twImagePreviewWrap', item.url, twPlaceholder);
                document.getElementById('btnRemoveTw')?.classList.remove('d-none');
                var label = document.getElementById('twImageBtnLabel');
                if (label) label.textContent = 'Change Twitter Image';
            }
        });
    });
    document.getElementById('btnRemoveTw')?.addEventListener('click', function () {
        document.getElementById('twImageUrlInput').value = '';
        setImagePreview('twImagePreviewWrap', '', twPlaceholder);
        this.classList.add('d-none');
        var label = document.getElementById('twImageBtnLabel');
        if (label) label.textContent = 'Choose Twitter Image';
    });

    function syncSlugPreview() {
        var slugEl = document.getElementById('seoSlugPreview');
        if (!slugEl) return;
        var src = document.getElementById('slug_hidden') || document.getElementById('slug');
        var val = (src && src.value) ? src.value : 'your-slug';
        slugEl.textContent = val || 'your-slug';
    }
    ['slug', 'slug_hidden', 'title'].forEach(function (id) {
        document.getElementById(id)?.addEventListener('input', syncSlugPreview);
        document.getElementById(id)?.addEventListener('change', syncSlugPreview);
    });
    syncSlugPreview();

    @if($manageSchemas)
    const SEO_CSRF = @json(csrf_token());
    const SCHEMA_STORE = @json($schemaStoreUrl ?? '');
    const SCHEMA_GEN = @json($schemaGenerateUrl ?? '');
    const SCHEMA_GEN_ALL = @json($schemaGenerateAllUrl ?? '');
    const SCHEMA_UPD = @json($schemaUpdateUrlBase ?? '');
    const SCHEMA_DEL = @json($schemaDeleteUrlBase ?? '');

    function seoFetch(url, method, body) {
        return fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': SEO_CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: method === 'DELETE' ? JSON.stringify(body || {}) : JSON.stringify(body || {}),
        }).then(function (r) {
            return r.json().then(function (j) {
                if (!r.ok && r.status !== 422) {
                    throw new Error(j.message || ('Server error ' + r.status));
                }
                return j;
            });
        }).catch(function (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Request Failed', text: err.message || 'Unexpected error' });
            } else {
                alert(err.message || 'Unexpected error');
            }
            throw err;
        });
    }

    function seoNotify(res, successMsg) {
        var msg = res.message || successMsg;
        if (res.success && typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: msg, showConfirmButton: false, timer: 2500, timerProgressBar: true });
        } else if (!res.success) {
            var err = res.errors ? Object.values(res.errors).flat().join('\n') : (res.message || 'Error');
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: err });
            else alert(err);
        }
    }

    function validateJsonField(textareaEl, errorEl) {
        var val = textareaEl.value.trim();
        if (!val) { errorEl.textContent = 'JSON is required.'; errorEl.style.display = ''; return false; }
        try { JSON.parse(val); errorEl.style.display = 'none'; return true; }
        catch (e) { errorEl.textContent = 'Invalid JSON: ' + e.message; errorEl.style.display = ''; return false; }
    }

    function schemaCount() {
        return document.querySelectorAll('#seoSchemaList .seo-schema-item').length;
    }
    function updateSchemaCountBadge() {
        var b = document.getElementById('schemaCountBadge');
        if (b) b.textContent = schemaCount();
    }
    function escHtml(str) {
        return String(str).replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
    }
    function renderSchemaRowHtml(data) {
        var autoLabel = data.is_auto_generated
            ? '<span class="badge bg-soft-info text-info ms-1">Auto</span>'
            : '<span class="badge bg-soft-secondary text-secondary ms-1">Manual</span>';
        var activeLabel = data.is_active
            ? '<span class="badge bg-soft-success text-success ms-1">Active</span>'
            : '<span class="badge bg-soft-secondary text-secondary ms-1">Inactive</span>';
        return '<div class="d-flex align-items-start justify-content-between gap-2">'
            + '<div><strong class="text-primary">' + escHtml(data.schema_type) + '</strong>'
            + (data.schema_name ? ' <span class="text-muted small">— ' + escHtml(data.schema_name) + '</span>' : '')
            + ' ' + autoLabel + ' ' + activeLabel + '</div>'
            + '<div class="d-flex gap-1">'
            + '<button type="button" class="btn btn-sm btn-soft-primary" onclick="openSchemaEdit(' + data.id + ')"><i class="ri-pencil-line"></i></button>'
            + '<button type="button" class="btn btn-sm btn-soft-danger" onclick="deleteSchemaRow(' + data.id + ')"><i class="ri-delete-bin-line"></i></button>'
            + '</div></div>';
    }
    function appendSchemaRow(data) {
        var list = document.getElementById('seoSchemaList');
        if (!list || !data || !data.id) return;
        var existing = list.querySelector('[data-id="' + data.id + '"]');
        if (existing) {
            existing.dataset.schema = JSON.stringify(data);
            existing.innerHTML = renderSchemaRowHtml(data);
            return;
        }
        var div = document.createElement('div');
        div.className = 'seo-schema-item seo-schema-row p-3 mb-2 border rounded';
        div.dataset.id = data.id;
        div.dataset.schema = JSON.stringify(data);
        div.innerHTML = renderSchemaRowHtml(data);
        list.appendChild(div);
    }
    function refreshSchemaList(schemas) {
        var list = document.getElementById('seoSchemaList');
        if (!list) return;
        list.innerHTML = '';
        (schemas || []).forEach(appendSchemaRow);
    }
    function updateSchemaRowData(id, data) {
        var row = document.querySelector('#seoSchemaList [data-id="' + id + '"]');
        if (!row) return;
        row.dataset.schema = JSON.stringify(data);
        row.innerHTML = renderSchemaRowHtml(data);
    }

    document.getElementById('btnAddSchema')?.addEventListener('click', function () {
        document.getElementById('seoSchemaAddForm').style.display = '';
        document.getElementById('newSchemaJson').value = '{\n  "@context": "https://schema.org",\n  "@type": "Article"\n}';
        document.getElementById('newSchemaJsonError').style.display = 'none';
    });
    document.getElementById('btnCancelNewSchema')?.addEventListener('click', function () {
        document.getElementById('seoSchemaAddForm').style.display = 'none';
    });
    document.getElementById('btnSubmitNewSchema')?.addEventListener('click', function () {
        var jsonEl = document.getElementById('newSchemaJson');
        var errEl = document.getElementById('newSchemaJsonError');
        if (!validateJsonField(jsonEl, errEl)) return;
        seoFetch(SCHEMA_STORE, 'POST', {
            schema_type: document.getElementById('newSchemaType').value,
            schema_name: document.getElementById('newSchemaName').value || null,
            schema_json: jsonEl.value,
            sort_order: parseInt(document.getElementById('newSchemaSortOrder').value, 10) || 0,
            is_active: document.getElementById('newSchemaActive').checked,
        }).then(function (res) {
            if (res.success) {
                appendSchemaRow(res.data || res.schema);
                document.getElementById('seoSchemaAddForm').style.display = 'none';
                updateSchemaCountBadge();
                seoNotify(res, 'Schema saved.');
            } else {
                seoNotify(res);
            }
        });
    });

    document.getElementById('btnGenerateSchema')?.addEventListener('click', function () {
        var type = document.getElementById('generateSchemaType').value;
        var run = function () {
            seoFetch(SCHEMA_GEN, 'POST', { schema_type: type }).then(function (res) {
                if (res.success) {
                    appendSchemaRow(res.data || res.schema);
                    updateSchemaCountBadge();
                    seoNotify(res, res.message);
                } else {
                    seoNotify(res);
                }
            });
        };
        if (typeof Swal === 'undefined') { if (confirm('Generate ' + type + '?')) run(); return; }
        Swal.fire({
            title: 'Generate Schema',
            text: 'Auto-generate a ' + type + ' schema from saved content?',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, generate', reverseButtons: true,
        }).then(function (result) { if (result.isConfirmed) run(); });
    });

    document.getElementById('btnGenerateAllSchemas')?.addEventListener('click', function () {
        if (!SCHEMA_GEN_ALL) return;
        var ask = typeof Swal !== 'undefined'
            ? Swal.fire({
                title: 'Generate All Schemas',
                html: '<p class="mb-2">Create Article, BlogPosting, WebPage, BreadcrumbList, FAQPage, and ImageObject from saved content.</p>'
                    + '<p class="text-muted small mb-0">FAQPage and ImageObject are skipped if no FAQs or featured image exist.</p>',
                icon: 'question', showCancelButton: true, showDenyButton: true,
                confirmButtonText: 'Append / refresh auto blocks',
                denyButtonText: 'Replace auto blocks',
                cancelButtonText: 'Cancel', reverseButtons: true,
            })
            : Promise.resolve({ isConfirmed: confirm('Generate all schemas?'), isDenied: false, isDismissed: false });

        ask.then(function (result) {
            if (result.isDismissed) return;
            seoFetch(SCHEMA_GEN_ALL, 'POST', { replace: !!result.isDenied }).then(function (res) {
                if (res.success) {
                    refreshSchemaList(res.schemas || []);
                    updateSchemaCountBadge();
                    seoNotify(res, res.message);
                } else {
                    seoNotify(res);
                }
            });
        });
    });

    window.openSchemaEdit = function (id) {
        var row = document.querySelector('#seoSchemaList [data-id="' + id + '"]');
        if (!row || !row.dataset.schema) { alert('Could not load schema data.'); return; }
        var d;
        try { d = JSON.parse(row.dataset.schema); }
        catch (e) { alert('Schema data is corrupted.'); return; }
        document.getElementById('editSchemaId').value = d.id;
        document.getElementById('editSchemaType').value = d.schema_type;
        document.getElementById('editSchemaName').value = d.schema_name || '';
        document.getElementById('editSchemaJson').value = d.schema_json || '';
        document.getElementById('editSchemaSortOrder').value = d.sort_order || 0;
        document.getElementById('editSchemaActive').checked = !!d.is_active;
        document.getElementById('editSchemaJsonError').style.display = 'none';
        new bootstrap.Modal(document.getElementById('seoSchemaEditModal')).show();
    };

    document.getElementById('btnSubmitEditSchema')?.addEventListener('click', function () {
        var id = document.getElementById('editSchemaId').value;
        var jsonEl = document.getElementById('editSchemaJson');
        var errEl = document.getElementById('editSchemaJsonError');
        if (!validateJsonField(jsonEl, errEl)) return;
        seoFetch(SCHEMA_UPD + '/' + id, 'PUT', {
            schema_type: document.getElementById('editSchemaType').value,
            schema_name: document.getElementById('editSchemaName').value || null,
            schema_json: jsonEl.value,
            sort_order: parseInt(document.getElementById('editSchemaSortOrder').value, 10) || 0,
            is_active: document.getElementById('editSchemaActive').checked,
        }).then(function (res) {
            if (res.success) {
                updateSchemaRowData(id, res.data || res.schema);
                bootstrap.Modal.getInstance(document.getElementById('seoSchemaEditModal'))?.hide();
                seoNotify(res, 'Schema updated.');
            } else {
                seoNotify(res);
            }
        });
    });

    window.deleteSchemaRow = function (id) {
        var run = function () {
            seoFetch(SCHEMA_DEL + '/' + id, 'DELETE', {}).then(function (res) {
                if (res.success) {
                    document.querySelector('#seoSchemaList [data-id="' + id + '"]')?.remove();
                    updateSchemaCountBadge();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: 'Deleted', text: 'Schema block removed.', icon: 'success', timer: 1500, showConfirmButton: false });
                    }
                } else {
                    seoNotify(res);
                }
            });
        };
        if (typeof Swal === 'undefined') { if (confirm('Delete this schema block?')) run(); return; }
        Swal.fire({
            title: 'Delete Schema Block',
            text: 'Are you sure you want to remove this schema block?',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it',
            confirmButtonColor: '#d33', reverseButtons: true,
        }).then(function (result) { if (result.isConfirmed) run(); });
    };
    @endif
});
</script>
