@extends('admin.layouts.vertical', ['title' => 'URL Redirects'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'URL Redirects',
    'crumbs' => ['SEO Management' => null, 'URL Redirects' => null],
    'back' => route('administrator.dashboard'),
    'actions' => (auth()->user()->can('seo.redirects.create') || auth()->user()->can('redirects.manage'))
        ? '<a class="btn btn-primary" href="'.route('administrator.redirects.create').'"><i class="ri-add-line me-1"></i> Add New</a>'
        : '',
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Redirects',
        'cardSubtitle' => 'Public 301/302 rules applied by CMS middleware',
    ])
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select class="form-select form-select-sm js-dt-filter" data-table="#redirects-table" data-column="2">
                    <option value="">All</option>
                    <option value="301">301</option>
                    <option value="302">302</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Active</label>
                <select class="form-select form-select-sm js-dt-filter" data-table="#redirects-table" data-column="3">
                    <option value="">All</option>
                    <option value="Active">Active</option>
                    <option value="Off">Off</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="redirects-table" data-search="Search redirects...">
                <thead class="table-light">
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Notes</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($redirects as $r)
                    <tr>
                        <td class="font-monospace">{{ $r->from_path }}</td>
                        <td>{{ $r->to_url }}</td>
                        <td><span class="badge {{ $r->status_code == 301 ? 'bg-soft-success text-success' : 'bg-soft-info text-info' }}">{{ $r->status_code }}</span></td>
                        <td><span class="badge {{ $r->active ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary' }}">{{ $r->active ? 'Active' : 'Off' }}</span></td>
                        <td>{{ $r->notes }}</td>
                        <td class="text-end">
                            @canany(['seo.redirects.delete','redirects.manage'])
                            <form class="d-inline" method="post" action="{{ route('administrator.redirects.destroy', $r) }}" onsubmit="return confirm('Delete redirect?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                            </form>
                            @endcanany
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
