@extends('admin.layouts.vertical', ['title' => 'SEO meta'])

@section('content')
<h3 class="mb-3">SEO meta</h3>
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'All meta records'])
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Type</th><th>ID</th><th>SEO title</th><th>Robots</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ class_basename($row->seoable_type) }}</td>
                    <td>{{ $row->seoable_id }}</td>
                    <td>{{ $row->seo_title ?: '—' }}</td>
                    <td>{{ $row->robots ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @include('admin.partials.pagination', ['paginator' => $rows])
</div>
@endsection
