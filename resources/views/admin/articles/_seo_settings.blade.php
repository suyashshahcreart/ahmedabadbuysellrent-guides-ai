@php
    $seo = $article->seo;
    $urlPrefix = ($article->type ?? 'guide') === 'compare' ? 'compare' : 'guides';
    $isEdit = (bool) $article->exists;
    $seoSchemas = $isEdit
        ? ($article->relationLoaded('seoSchemas')
            ? $article->seoSchemas
            : $article->seoSchemas()->orderBy('sort_order')->orderBy('id')->get())
        : collect();
@endphp
<div class="card panel-card border-primary border-top mb-3" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', [
        'cardTitle' => 'SEO Settings',
        'cardSubtitle' => 'Meta tags and social cards — saved when you save the post',
        'panelCollapsed' => true,
    ])
    <div class="card-body">
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-sm btn-soft-primary js-ask-ai">
                <i class="ri-magic-line me-1"></i>Ask AI — Auto-fill SEO
            </button>
        </div>
        @include('admin.seo._seo_form', [
            'seoMeta' => $seo,
            'embeddedMode' => true,
            'manageSchemas' => $isEdit,
            'isEdit' => $isEdit,
            'article' => $article,
            'seoSchemas' => $seoSchemas,
            'schemaTypes' => \App\Services\SchemaSync::supportedTypes(),
            'urlPrefix' => $urlPrefix,
            'slugPreview' => old('slug', $article->slug ?: 'your-slug'),
            'schemaStoreUrl' => $isEdit ? route('administrator.articles.seo.schemas.store', $article) : '',
            'schemaGenerateUrl' => $isEdit ? route('administrator.articles.seo.schemas.generate', $article) : '',
            'schemaGenerateAllUrl' => $isEdit ? route('administrator.articles.seo.schemas.generate-all', $article) : '',
            'schemaUpdateUrlBase' => $isEdit ? rtrim(route('administrator.articles.seo.schemas.update', [$article, 999999]), '/999999') : '',
            'schemaDeleteUrlBase' => $isEdit ? rtrim(route('administrator.articles.seo.schemas.delete', [$article, 999999]), '/999999') : '',
        ])
    </div>
</div>
