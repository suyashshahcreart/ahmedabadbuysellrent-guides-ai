@php
    $selectedTags = old('tag_ids')
        ? \App\Models\Tag::whereIn('id', (array) old('tag_ids'))->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()->all()
        : ($article->relationLoaded('tags') || $article->exists
            ? $article->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()->all()
            : []);
    if (old('tag_names')) {
        foreach ((array) old('tag_names') as $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $selectedTags[] = ['id' => null, 'name' => $name];
        }
    }
    $mostUsedTags = $mostUsedTags ?? collect();
@endphp
<div class="card panel-card border-primary border-top mb-3" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', [
        'cardTitle' => 'Tags',
        'cardSubtitle' => 'Type to add; new tags are saved automatically',
    ])
    <div class="card-body">
        <div class="position-relative mb-1">
            <input type="text" id="tagInput" class="form-control form-control-sm"
                   placeholder="Add tag, press Enter or comma…" autocomplete="off">
            <div id="tagSuggestions"
                 class="d-none position-absolute w-100 border rounded bg-white shadow-sm"
                 style="z-index:1050;top:100%;max-height:160px;overflow-y:auto;">
            </div>
        </div>
        <p class="form-text mb-2 small">Separate with commas or Enter. New tags are created on save.</p>

        <div id="selectedTagsWrap" class="d-flex flex-wrap gap-1 mb-2" style="min-height:30px;"></div>

        @if($mostUsedTags->isNotEmpty())
        <div class="border-top pt-2 mt-1">
            <a href="#" id="toggleMostUsed" class="text-primary small text-decoration-none">
                <i class="ri-price-tag-3-line me-1"></i>Choose from most used tags
            </a>
            <div id="mostUsedTagsList" class="d-none mt-2 d-flex flex-wrap gap-1">
                @foreach($mostUsedTags as $tag)
                    <span class="badge rounded-pill text-bg-light text-dark border small px-2 py-1"
                          style="cursor:pointer;"
                          data-tag-id="{{ $tag->id }}"
                          data-tag-name="{{ $tag->name }}"
                          data-suggest-id="{{ $tag->id }}"
                          data-suggest-name="{{ $tag->name }}">
                        {{ $tag->name }}
                        <span class="text-muted">({{ $tag->articles_count }})</span>
                    </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script>
window.blogTaxonomyConfig = Object.assign({}, window.blogTaxonomyConfig || {}, {
    tagSuggestUrl: @json(route('administrator.articles.tags.suggest')),
    csrfToken: @json(csrf_token()),
    initialTags: @json($selectedTags),
});
</script>
