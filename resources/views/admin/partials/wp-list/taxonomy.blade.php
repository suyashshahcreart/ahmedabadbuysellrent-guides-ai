@php
    $items = $items ?? collect();
    $visible = $visible ?? 3;
    $type = $type ?? 'tag';
    $filterKey = $filterKey ?? 'tag_id';
@endphp
@if($items->isEmpty())
    <span class="post-wp-empty">—</span>
@else
    <div class="post-wp-taxonomy-list {{ $type === 'tag' ? 'post-wp-taxonomy-list--tags' : '' }}">
        @foreach($items->take($visible) as $item)
            <a href="{{ $indexUrl }}?{{ http_build_query(array_filter(array_merge(request()->except('page'), [$filterKey => $item->id]))) }}"
               class="post-wp-taxonomy-badge post-wp-taxonomy-badge--{{ $type }} post-wp-filter-link">{{ $item->name }}</a>
        @endforeach
        @if($items->count() > $visible)
            <span class="post-wp-taxonomy-extra is-collapsed">
                @foreach($items->slice($visible) as $item)
                    <a href="{{ $indexUrl }}?{{ http_build_query(array_filter(array_merge(request()->except('page'), [$filterKey => $item->id]))) }}"
                       class="post-wp-taxonomy-badge post-wp-taxonomy-badge--{{ $type }} post-wp-filter-link">{{ $item->name }}</a>
                @endforeach
            </span>
            <button type="button" class="post-wp-taxonomy-more-toggle" data-more-count="{{ $items->count() - $visible }}" aria-expanded="false">+{{ $items->count() - $visible }} more</button>
        @endif
    </div>
@endif
