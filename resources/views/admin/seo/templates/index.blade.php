@extends('admin.layouts.vertical', ['title' => 'SEO Templates'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'SEO Templates',
    'crumbs' => ['SEO Management' => null, 'Templates' => null],
    'back' => route('administrator.dashboard'),
    'actions' => (auth()->user()->can('seo.templates.manage') || auth()->user()->can('seo.create'))
        ? '<a class="btn btn-primary" href="'.route('administrator.seo.templates.create').'"><i class="ri-add-line me-1"></i> Add New</a>'
        : '',
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'SEO Templates List',
        'cardSubtitle' => 'Default SEO patterns for Guides page types',
    ])
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="seo-templates-table" data-search="Search templates...">
                <thead class="table-light">
                    <tr>
                        <th>Page Type</th>
                        <th>Model</th>
                        <th>Title Template</th>
                        <th>Schema Type</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($templates as $template)
                    <tr>
                        <td class="fw-semibold">{{ $template->page_type }}</td>
                        <td>{{ $template->model_type ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($template->title_template, 50) }}</td>
                        <td>{{ $template->schema_type ?: '—' }}</td>
                        <td><span class="badge {{ $template->is_active ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-nowrap text-muted">{{ optional($template->updated_at)->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-soft-primary" href="{{ route('administrator.seo.templates.edit', $template) }}"><i class="ri-pencil-line"></i></a>
                            <form class="d-inline" method="post" action="{{ route('administrator.seo.templates.destroy', $template) }}" onsubmit="return confirm('Delete template?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
