@php
    $indexUrl = route('administrator.articles.index');
    $queryBase = request()->except('page');
    $tabUrl = function ($key) use ($indexUrl, $queryBase) {
        $q = $queryBase;
        $q['status'] = $key === 'all' ? null : $key;
        return $indexUrl.'?'.http_build_query(array_filter($q, fn ($v) => $v !== null && $v !== ''));
    };
    $selectedCategory = $filterOptions['categories']->firstWhere('id', (int) request('category_id'));
    $selectedTag = $filterOptions['tags']->firstWhere('id', (int) request('tag_id'));
    $selectedAuthor = $filterOptions['authors']->firstWhere('id', (int) request('author_id'));
    $viewLabels = ['all' => 'All', 'published' => 'Published', 'draft' => 'Draft', 'trash' => 'Trash', 'featured' => 'Featured'];
@endphp
@extends('admin.layouts.vertical', ['title' => 'All Guides'])

@section('css')
<link href="{{ asset('admin/css/post-list-wp.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Guides</li>
            </ol>
        </nav>
        <h3 class="mb-0">Guides</h3>
    </div>
    @can('articles.create')
    <a href="{{ route('administrator.articles.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i> Add New</a>
    @endcan
</div>

<div class="card panel-card border-primary border-top mb-3" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Filters',
        'cardSubtitle' => 'Category, tag, author, status and date',
    ])
    <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-end js-wp-auto-filter">
            <input type="hidden" name="status" value="{{ $activeView === 'all' ? '' : $activeView }}">
            <div class="col-md-6 col-lg">
                <label class="form-label small text-muted mb-1">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($filterOptions['categories'] as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg">
                <label class="form-label small text-muted mb-1">Tag</label>
                <select name="tag_id" class="form-select form-select-sm">
                    <option value="">All Tags</option>
                    @foreach($filterOptions['tags'] as $tag)
                        <option value="{{ $tag->id }}" @selected(request('tag_id') == $tag->id)>{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg">
                <label class="form-label small text-muted mb-1">Author</label>
                <select name="author_id" class="form-select form-select-sm">
                    <option value="">All Authors</option>
                    @foreach($filterOptions['authors'] as $author)
                        <option value="{{ $author->id }}" @selected(request('author_id') == $author->id)>{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg">
                <label class="form-label small text-muted mb-1">Date</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">All Dates</option>
                    @foreach($months as $ym)
                        <option value="{{ $ym }}" @selected(request('month') === $ym)>{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card panel-card border-primary border-top post-wp-list-card" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'All Guides',
        'cardSubtitle' => 'Manage editorial guides and comparisons',
    ])

    <div class="post-wp-filter-summary">
        <small class="text-muted">
            <i class="ri-filter-3-line me-1"></i>
            Showing posts
            <span> | View: {{ $viewLabels[$activeView] ?? 'All' }}</span>
            <span> | for {{ request('month') ? \Carbon\Carbon::createFromFormat('Y-m', request('month'))->format('F Y') : 'All Dates' }}</span>
            @unless($activeView === 'trash')
                <span> | Category: {{ $selectedCategory->name ?? 'All Categories' }}</span>
                <span> | Tag: {{ $selectedTag->name ?? 'All Tags' }}</span>
                <span> | Author: {{ $selectedAuthor->name ?? 'All Authors' }}</span>
                <span> | Status: {{ $viewLabels[$activeView] === 'All' ? 'All Status' : ($viewLabels[$activeView] ?? 'All Status') }}</span>
            @endunless
        </small>
    </div>

    <div class="card-body p-0">
        <div class="post-wp-list-toolbar">
            <ul class="post-wp-subnav">
                @foreach(['all' => 'All', 'published' => 'Published', 'draft' => 'Draft', 'trash' => 'Trash', 'featured' => 'Featured'] as $key => $label)
                    <li>
                        <a href="{{ $tabUrl($key) }}" class="{{ $activeView === $key ? 'is-active' : '' }}">
                            {{ $label }} ({{ $counts[$key] ?? 0 }})
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="post-wp-list-toolbar-end">
                <div class="post-wp-search-wrap">
                    <input type="search" id="guidesListSearch" class="form-control form-control-sm post-wp-search-input" placeholder="Search posts" autocomplete="off">
                </div>
                <span class="post-wp-items-count" id="guidesListCount">{{ $articles->count() }} items</span>
            </div>
        </div>

        <form method="post" action="{{ route('administrator.articles.bulk') }}">
            @csrf
            <div class="post-wp-toolbar post-wp-toolbar-top bg-light border-bottom">
                <select name="bulk_action" class="form-select form-select-sm post-wp-bulk-select">
                    @if($activeView === 'trash')
                        <option value="restore">Restore</option>
                        <option value="delete">Delete permanently</option>
                    @else
                        <option value="trash">Move to Trash</option>
                    @endif
                </select>
                <button class="btn btn-sm btn-outline-secondary" type="submit">Apply</button>
            </div>
            <div class="table-responsive post-wp-table-wrap">
                <table class="table align-middle mb-0 post-wp-table" id="guidesListTable">
                    <thead>
                        <tr>
                            <th class="post-wp-check-col">
                                <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.js-bulk-id').forEach(c=>c.checked=this.checked)">
                            </th>
                            <th class="post-wp-title-col">Title</th>
                            <th class="post-wp-author-col">Author</th>
                            <th class="post-wp-cat-col">Categories</th>
                            <th class="post-wp-tags-col">Tags</th>
                            <th class="post-wp-date-col">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($articles as $a)
                        <tr data-wp-row data-search="{{ strtolower($a->title.' '.$a->author?->name.' '.$a->category?->name.' '.$a->tags->pluck('name')->join(' ')) }}">
                            <td class="post-wp-check-col"><input type="checkbox" class="form-check-input js-bulk-id" name="ids[]" value="{{ $a->id }}"></td>
                            <td class="post-wp-title-col">
                                <div class="post-wp-title-cell">
                                    <div class="post-wp-title-main">
                                        @if($a->trashed())
                                            <strong class="post-wp-title-text">{{ $a->title }}</strong>
                                        @else
                                            <a href="{{ url('/administrator/articles/'.$a->id.'/edit') }}" class="post-wp-title-link">{{ $a->title }}</a>
                                        @endif
                                    </div>
                                    <div class="post-wp-row-actions">
                                        @if($a->trashed())
                                            <form method="post" action="{{ route('administrator.articles.restore', $a->id) }}" class="d-inline">@csrf
                                                <button type="submit" class="post-wp-action-link border-0 bg-transparent p-0">Restore</button>
                                            </form>
                                            <span class="post-wp-action-sep"> | </span>
                                            <form method="post" action="{{ url('/administrator/articles/'.$a->id) }}" class="d-inline" onsubmit="return confirm('Delete permanently?')">@csrf @method('DELETE')
                                                <button type="submit" class="post-wp-action-link post-wp-action-trash border-0 bg-transparent p-0">Delete</button>
                                            </form>
                                        @else
                                            @can('articles.edit')
                                                <a href="{{ url('/administrator/articles/'.$a->id.'/edit') }}" class="post-wp-action-link">Edit</a>
                                                <span class="post-wp-action-sep"> | </span>
                                                <button type="button" class="post-wp-action-link border-0 bg-transparent p-0" data-wp-quick-edit="{{ $a->id }}">Quick Edit</button>
                                            @endcan
                                            @can('articles.create')
                                                <span class="post-wp-action-sep"> | </span>
                                                <form method="post" action="{{ route('administrator.articles.duplicate', $a) }}" class="d-inline">@csrf
                                                    <button type="submit" class="post-wp-action-link border-0 bg-transparent p-0">Clone</button>
                                                </form>
                                            @endcan
                                            @can('articles.delete')
                                                <span class="post-wp-action-sep"> | </span>
                                                <form method="post" action="{{ url('/administrator/articles/'.$a->id) }}" class="d-inline">@csrf @method('DELETE')
                                                    <button type="submit" class="post-wp-action-link post-wp-action-trash border-0 bg-transparent p-0">Trash</button>
                                                </form>
                                            @endcan
                                            @if($a->status === 'published')
                                                <span class="post-wp-action-sep"> | </span>
                                                <a href="{{ $a->publicUrl() }}" class="post-wp-action-link" target="_blank" rel="noopener">View</a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="post-wp-author-col">{{ $a->author?->name ?? '—' }}</td>
                            <td class="post-wp-cat-col">
                                @if($a->category)
                                    @include('admin.partials.wp-list.taxonomy', [
                                        'items' => collect([$a->category]),
                                        'type' => 'category',
                                        'filterKey' => 'category_id',
                                        'indexUrl' => $indexUrl,
                                        'visible' => 4,
                                    ])
                                @else
                                    <span class="post-wp-empty">—</span>
                                @endif
                            </td>
                            <td class="post-wp-tags-col">
                                @include('admin.partials.wp-list.taxonomy', [
                                    'items' => $a->tags,
                                    'type' => 'tag',
                                    'filterKey' => 'tag_id',
                                    'indexUrl' => $indexUrl,
                                    'visible' => 3,
                                ])
                            </td>
                            <td class="post-wp-date-col">
                                @include('admin.partials.wp-list.date-cell', [
                                    'status' => $a->trashed() ? 'trash' : $a->status,
                                    'when' => $a->published_at ?: $a->updated_at,
                                ])
                            </td>
                        </tr>
                        @if(! $a->trashed())
                        <tr class="wp-quick-edit-row d-none" id="wp-quick-edit-{{ $a->id }}">
                            <td colspan="6" class="bg-light">
                                <form method="post" action="{{ route('administrator.articles.quick', $a) }}" class="p-3">
                                    @csrf @method('PUT')
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label small mb-1">Title</label>
                                            <input class="form-control form-control-sm" name="title" value="{{ $a->title }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Slug</label>
                                            <input class="form-control form-control-sm" name="slug" value="{{ $a->slug }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Status</label>
                                            <select class="form-select form-select-sm" name="status">
                                                @foreach(['draft','published','scheduled','archived'] as $st)
                                                    <option value="{{ $st }}" @selected($a->status===$st)>{{ ucfirst($st) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Category</label>
                                            <select class="form-select form-select-sm" name="category_id">
                                                <option value="">—</option>
                                                @foreach($filterOptions['categories'] as $category)
                                                    <option value="{{ $category->id }}" @selected($a->category_id==$category->id)>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                            <button class="btn btn-sm btn-soft-secondary" type="button" data-wp-quick-edit="{{ $a->id }}">Cancel</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="author_id" value="{{ $a->author_id }}">
                                    @if($a->featured)<input type="hidden" name="featured" value="1">@endif
                                </form>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No guides found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script-bottom')
<script src="{{ asset('admin/js/post-list-taxonomy-toggle.js') }}"></script>
<script src="{{ asset('admin/js/wp-list.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bindWpListSearch('guidesListSearch', 'guidesListTable', 'guidesListCount');
});
</script>
@endsection
