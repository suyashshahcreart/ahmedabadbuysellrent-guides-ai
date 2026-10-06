@extends('admin.layouts.vertical', ['title' => 'SEO Meta'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'SEO Meta',
    'crumbs' => ['SEO Management' => null, 'SEO Meta' => null],
    'back' => route('administrator.dashboard'),
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'All SEO Meta Records',
        'cardSubtitle' => 'Review and manage SEO meta tags across all entities',
    ])
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="seo-meta-table" data-search="Search SEO meta...">
                <thead class="table-light">
                    <tr>
                        <th>Model</th>
                        <th>SEO Title</th>
                        <th>Robots</th>
                        <th>Updated</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ class_basename($row->seoable_type) }} #{{ $row->seoable_id }}</td>
                        <td>{{ $row->seo_title ?: '—' }}</td>
                        <td><span class="badge bg-soft-secondary text-secondary">{{ $row->robots ?: 'index, follow' }}</span></td>
                        <td class="text-nowrap text-muted">{{ optional($row->updated_at)->format('M d, Y') }}</td>
                        <td class="text-end">
                            @canany(['seo.edit','seo.manage'])
                            <a class="btn btn-sm btn-soft-primary" href="{{ route('administrator.seo.meta.edit', $row) }}" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line"></i></a>
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
