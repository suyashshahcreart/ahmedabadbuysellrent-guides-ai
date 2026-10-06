@extends('admin.layouts.vertical', ['title' => $title])
@section('css')
<link href="{{ asset('admin/css/post-list-wp.css') }}" rel="stylesheet">
@endsection
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">{{ $title }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $title }}</h3>
    </div>
    @if(!empty($createUrl))
    <a class="btn btn-primary" href="{{ $createUrl }}"><i class="ri-add-line me-1"></i> Add New</a>
    @endif
</div>
<div class="card panel-card border-primary border-top post-wp-list-card" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => $title, 'cardSubtitle' => 'Search and manage this list'])
    <div class="post-wp-filter-summary">
        <small class="text-muted"><i class="ri-filter-3-line me-1"></i> Showing {{ strtolower($title) }} | View: All</small>
    </div>
    <div class="card-body p-0">
        <div class="post-wp-list-toolbar">
            <ul class="post-wp-subnav">
                <li><a href="#" class="is-active">All ({{ count($rows) }})</a></li>
            </ul>
            <div class="post-wp-list-toolbar-end">
                <input type="search" id="simpleListSearch" class="form-control form-control-sm post-wp-search-input" placeholder="Search {{ strtolower($title) }}" autocomplete="off">
                <span class="post-wp-items-count" id="simpleListCount">{{ count($rows) }} items</span>
            </div>
        </div>
        <div class="table-responsive post-wp-table-wrap">
            <table class="table align-middle mb-0 post-wp-table" id="simpleListTable">
                <thead>
                    <tr>
                        <th class="post-wp-title-col">Title</th>
                        <th class="post-wp-date-col">ID</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr data-wp-row data-search="{{ strtolower($row['label']) }}">
                        <td class="post-wp-title-col">
                            <div class="post-wp-title-cell">
                                <div class="post-wp-title-main">
                                    <a href="{{ $row['edit'] }}" class="post-wp-title-link">{{ $row['label'] }}</a>
                                </div>
                                <div class="post-wp-row-actions">
                                    <a href="{{ $row['edit'] }}" class="post-wp-action-link">Edit</a>
                                </div>
                            </div>
                        </td>
                        <td class="post-wp-date-col">{{ $row['id'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">No items found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('script-bottom')
<script src="{{ asset('admin/js/wp-list.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded', function () { bindWpListSearch('simpleListSearch', 'simpleListTable', 'simpleListCount'); });</script>
@endsection
