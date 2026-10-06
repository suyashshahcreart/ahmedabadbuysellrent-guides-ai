@extends('admin.layouts.vertical', ['title' => 'Schema Library'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Schema Library',
    'crumbs' => ['SEO Management' => null, 'Schema Library' => null],
    'back' => route('administrator.dashboard'),
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'All JSON-LD Schemas',
        'cardSubtitle' => 'Structured data schemas attached to entities',
    ])
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="seo-schemas-table" data-search="Search schemas...">
                <thead class="table-light">
                    <tr>
                        <th>Model</th>
                        <th>Schema Type</th>
                        <th>Label</th>
                        <th>Auto</th>
                        <th>Active</th>
                        <th>Sort</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($schemas as $schema)
                    <tr>
                        <td>{{ class_basename($schema->schemaable_type) }} #{{ $schema->schemaable_id }}</td>
                        <td>{{ $schema->schema_type }}</td>
                        <td>{{ $schema->schema_name }}</td>
                        <td>{{ $schema->is_auto_generated ? 'Yes' : 'No' }}</td>
                        <td>{{ $schema->is_active ? 'Yes' : 'No' }}</td>
                        <td>{{ $schema->sort_order }}</td>
                        <td class="text-nowrap text-muted">{{ optional($schema->created_at)->format('M d, Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
