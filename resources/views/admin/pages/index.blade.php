@extends('admin.layouts.vertical', ['title' => 'Pages'])
@section('css')
<link href="{{ asset('admin/css/post-list-wp.css') }}" rel="stylesheet">
@endsection
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Pages</li>
            </ol>
        </nav>
        <h3 class="mb-0">Pages</h3>
    </div>
    @canany(['pages.create','pages.manage'])
    <a class="btn btn-primary" href="/administrator/pages/create"><i class="ri-add-line me-1"></i> Add New</a>
    @endcanany
</div>

<div class="card panel-card border-primary border-top post-wp-list-card" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'All pages', 'cardSubtitle' => 'Static CMS pages'])
    <div class="post-wp-filter-summary">
        <small class="text-muted"><i class="ri-filter-3-line me-1"></i> Showing pages | View: {{ ucfirst($activeView) }}</small>
    </div>
    <div class="card-body p-0">
        <div class="post-wp-list-toolbar">
            <ul class="post-wp-subnav">
                @foreach(['all' => 'All', 'published' => 'Published', 'draft' => 'Draft'] as $key => $label)
                    <li>
                        <a href="/administrator/pages?status={{ $key === 'all' ? '' : $key }}" class="{{ $activeView === $key ? 'is-active' : '' }}">
                            {{ $label }} ({{ $counts[$key] ?? 0 }})
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="post-wp-list-toolbar-end">
                <input type="search" id="pagesListSearch" class="form-control form-control-sm post-wp-search-input" placeholder="Search pages" autocomplete="off">
                <span class="post-wp-items-count" id="pagesListCount">{{ $pages->count() }} items</span>
            </div>
        </div>
        <div class="table-responsive post-wp-table-wrap">
            <table class="table align-middle mb-0 post-wp-table" id="pagesListTable">
                <thead>
                    <tr>
                        <th class="post-wp-title-col">Title</th>
                        <th>Slug</th>
                        <th class="post-wp-date-col">Date</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pages as $p)
                    <tr data-wp-row data-search="{{ strtolower($p->title.' '.$p->slug) }}">
                        <td class="post-wp-title-col">
                            <div class="post-wp-title-cell">
                                <div class="post-wp-title-main">
                                    <a href="/administrator/pages/{{ $p->id }}/edit" class="post-wp-title-link">{{ $p->title }}</a>
                                </div>
                                <div class="post-wp-row-actions">
                                    <a href="/administrator/pages/{{ $p->id }}/edit" class="post-wp-action-link">Edit</a>
                                    <span class="post-wp-action-sep"> | </span>
                                    <form method="post" action="/administrator/pages/{{ $p->id }}" class="d-inline">@csrf @method('DELETE')
                                        <button type="submit" class="post-wp-action-link post-wp-action-trash border-0 bg-transparent p-0">Trash</button>
                                    </form>
                                    @if($p->status === 'published')
                                        <span class="post-wp-action-sep"> | </span>
                                        <a href="{{ $p->publicUrl() }}" class="post-wp-action-link" target="_blank" rel="noopener">View</a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $p->slug }}</td>
                        <td class="post-wp-date-col">
                            @include('admin.partials.wp-list.date-cell', ['status' => $p->status, 'when' => $p->updated_at])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No pages found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('script-bottom')
<script src="{{ asset('admin/js/wp-list.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded', function () { bindWpListSearch('pagesListSearch', 'pagesListTable', 'pagesListCount'); });</script>
@endsection
