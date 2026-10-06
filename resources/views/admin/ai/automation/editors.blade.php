@extends('admin.layouts.vertical', ['title' => 'AI Editors'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Rotating editors',
    'subheading' => 'AI posts rotate across these Author profiles.',
    'crumbs' => ['AI' => route('administrator.ai.automation.index'), 'Editors' => null],
    'back' => route('administrator.ai.automation.index'),
    'actions' => auth()->user()->can('ai.automation.manage')
        ? '<form method="post" action="'.route('administrator.ai.automation.ensure-editors').'" class="d-inline">'.csrf_field().'<button class="btn btn-primary">Ensure 10 editors</button></form>'
        : '',
])

<div class="card panel-card">
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Editors', 'cardSubtitle' => count($editors).' profiles'])
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Slug</th><th>AI articles</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($editors as $editor)
                <tr>
                    <td>{{ $editor->name }}</td>
                    <td>{{ $editor->slug }}</td>
                    <td>{{ $editor->articles_count }}</td>
                    <td>{{ $editor->status ? 'active' : 'off' }}</td>
                    <td><a href="{{ route('administrator.authors.edit', $editor) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-3 text-muted">No editors yet. Click Ensure 10 editors.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
