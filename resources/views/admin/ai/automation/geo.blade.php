@extends('admin.layouts.vertical', ['title' => 'AI Geo & Angles'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Geo pools & angles',
    'subheading' => 'Ahmedabad zones, areas, roads, landmarks and title angles.',
    'crumbs' => ['AI' => route('administrator.ai.automation.index'), 'Geo' => null],
    'back' => route('administrator.ai.automation.index'),
    'actions' => auth()->user()->can('ai.automation.manage')
        ? '<form method="post" action="'.route('administrator.ai.automation.seed-pools').'" class="d-inline">'.csrf_field().'<button class="btn btn-outline-primary">Re-seed pools</button></form>'
        : '',
])

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card panel-card">
            @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Geo nodes ('.count($nodes).')', 'cardSubtitle' => 'Used for auto titles'])
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:520px;overflow:auto">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Type</th><th>Name</th><th>Parent</th><th>Active</th></tr></thead>
                        <tbody>
                        @foreach($nodes as $node)
                            <tr>
                                <td>{{ $node->type }}</td>
                                <td>{{ $node->name }}</td>
                                <td>{{ $node->parent?->name ?? '—' }}</td>
                                <td>{{ $node->status ? 'yes' : 'no' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card panel-card">
            @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Angles ('.count($angles).')', 'cardSubtitle' => 'Title patterns'])
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:520px;overflow:auto">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Name</th><th>Intent</th><th>Pattern</th></tr></thead>
                        <tbody>
                        @foreach($angles as $angle)
                            <tr>
                                <td>{{ $angle->name }}</td>
                                <td>{{ $angle->intent }}</td>
                                <td class="small">{{ $angle->title_pattern }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
