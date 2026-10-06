@extends('admin.layouts.vertical', ['title' => 'AI Structure Templates'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Structure templates',
    'subheading' => 'Editable section order and keyword rules (from Blog Ideas sheet).',
    'crumbs' => ['AI' => route('administrator.ai.automation.index'), 'Templates' => null],
    'back' => route('administrator.ai.automation.index'),
])

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

@foreach($templates as $template)
<div class="card panel-card mb-3">
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => $template->name.' ('.$template->key.')',
        'cardSubtitle' => 'Type: '.$template->content_type,
    ])
    <div class="card-body">
        <form method="post" action="{{ route('administrator.ai.automation.templates.update', $template) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $template->name }}" @disabled(!auth()->user()->can('ai.automation.manage'))>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="status{{ $template->id }}" @checked($template->status) @disabled(!auth()->user()->can('ai.automation.manage'))>
                        <label class="form-check-label" for="status{{ $template->id }}">Active</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sections (one per line)</label>
                    <textarea name="sections_text" class="form-control font-monospace" rows="10" @disabled(!auth()->user()->can('ai.automation.manage'))>{{ implode("\n", $template->sections ?? []) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Keyword rules JSON</label>
                    <textarea name="keyword_rules_json" class="form-control font-monospace" rows="10" @disabled(!auth()->user()->can('ai.automation.manage'))>{{ json_encode($template->keyword_rules, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" @disabled(!auth()->user()->can('ai.automation.manage'))>{{ $template->notes }}</textarea>
                </div>
            </div>
            @can('ai.automation.manage')
            <button class="btn btn-primary mt-3" type="submit">Save template</button>
            @endcan
        </form>
    </div>
</div>
@endforeach
@endsection
