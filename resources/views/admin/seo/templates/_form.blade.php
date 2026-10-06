@php
    $pageTypes = config('seo.page_types');
    $templateTokens = config('seo.tokens');
    $schemaTypes = ['Article', 'WebPage', 'FAQPage', 'BreadcrumbList'];
    $robotsOptions = [
        'index, follow' => 'Index, follow',
        'noindex, follow' => 'Noindex, follow',
        'index, nofollow' => 'Index, nofollow',
        'noindex, nofollow' => 'Noindex, nofollow',
    ];
@endphp
<div class="row">
    <div class="col-lg-4 mb-3">
        <label class="form-label">Page Type <span class="text-danger">*</span></label>
        <select name="page_type" class="form-select" required>
            <option value="">— Select —</option>
            @foreach($pageTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('page_type', $template?->page_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-4 mb-3">
        <label class="form-label">Model Type</label>
        <input class="form-control" name="model_type" value="{{ old('model_type', $template?->model_type) }}" placeholder="Optional morph class">
    </div>
    <div class="col-lg-4 mb-3">
        <label class="form-label">Default Robots</label>
        <select name="default_robots" class="form-select">
            @foreach($robotsOptions as $val => $label)
                <option value="{{ $val }}" @selected(old('default_robots', $template?->default_robots ?? 'index, follow') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Title Template</label>
        <input class="form-control" name="title_template" value="{{ old('title_template', $template?->title_template) }}" placeholder="{title} | {site}">
        <div class="form-text">Tokens: @foreach($templateTokens as $token => $hint)<code>{!! '{'.$token.'}' !!}</code>@if(!$loop->last), @endif @endforeach</div>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Description Template</label>
        <textarea class="form-control" name="description_template" rows="2">{{ old('description_template', $template?->description_template) }}</textarea>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">H1 Template</label>
        <input class="form-control" name="h1_template" value="{{ old('h1_template', $template?->h1_template) }}">
    </div>
    <div class="col-lg-4 mb-3">
        <label class="form-label">Default Schema Type</label>
        <select name="schema_type" class="form-select">
            <option value="">— None —</option>
            @foreach($schemaTypes as $type)
                <option value="{{ $type }}" @selected(old('schema_type', $template?->schema_type) === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Schema Template JSON</label>
        <textarea class="form-control font-monospace" name="schema_template_json" rows="6">{{ old('schema_template_json', $template?->schema_template_json) }}</textarea>
    </div>
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $template?->is_active ?? true))>
            <label class="form-check-label">Active</label>
        </div>
    </div>
</div>
