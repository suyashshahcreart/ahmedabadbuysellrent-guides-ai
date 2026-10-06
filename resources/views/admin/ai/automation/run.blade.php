@extends('admin.layouts.vertical', ['title' => 'AI Run #'.$run->id])
@section('content')
@php
    $total = (int) $run->target;
    $generated = (int) $run->completed;
    $pending = max(0, $total - $generated);
    $tz = config('app.timezone', 'Asia/Kolkata');
    $isStopped = in_array($run->status, ['paused', 'completed', 'failed'], true);
@endphp
@include('admin.partials.page-head', [
    'heading' => 'Run #'.$run->id,
    'subheading' => 'Status: '.$run->status.' · Progress: '.$total.'/'.$generated.' · Pending: '.$pending.' · Started: '.optional($run->started_at)->timezone($tz)->format('Y-m-d H:i:s'),
    'crumbs' => ['AI' => route('administrator.ai.automation.index'), 'Reports' => route('administrator.ai.automation.reports'), 'Run' => null],
    'back' => route('administrator.ai.automation.reports'),
    'actions' => auth()->user()->can('ai.automation.manage')
        ? (
            (!$isStopped || $run->status === 'paused'
                ? '<form method="post" action="'.route('administrator.ai.automation.runs.start', $run).'" class="d-inline">'.csrf_field().'<button class="btn btn-success me-1" type="submit"><i class="ri-play-line me-1"></i>Start</button></form>'
                : '')
            .
            (!in_array($run->status, ['completed', 'failed'], true)
                ? '<form method="post" action="'.route('administrator.ai.automation.runs.stop', $run).'" class="d-inline">'.csrf_field().'<button class="btn btn-warning" type="submit"><i class="ri-stop-line me-1"></i>Stop</button></form>'
                : '')
          )
        : '',
])

<div class="row g-3 mb-3">
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Total / Generated</div><div class="fs-4">{{ $total }}/{{ $generated }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Pending</div><div class="fs-4">{{ $pending }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Failed</div><div class="fs-4">{{ $run->failed }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Duplicates</div><div class="fs-4">{{ $run->duplicate_skipped }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Running</div><div class="fs-4">{{ $run->running }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Tokens</div><div class="fs-6">{{ number_format($run->input_tokens) }} / {{ number_format($run->output_tokens) }}</div></div></div></div>
</div>

@can('ai.automation.manage')
<div class="card panel-card mb-3">
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Run controls',
        'cardSubtitle' => 'Start / Stop only this run. Other active runs keep working in background.',
    ])
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if($run->status !== 'completed' && $run->status !== 'failed')
                <form method="post" action="{{ route('administrator.ai.automation.runs.start', $run) }}">
                    @csrf
                    <button class="btn btn-success" type="submit" @disabled($run->status === 'running')>
                        <i class="ri-play-line me-1"></i> Start this run
                    </button>
                </form>
                <form method="post" action="{{ route('administrator.ai.automation.runs.stop', $run) }}">
                    @csrf
                    <button class="btn btn-warning" type="submit" @disabled($run->status === 'paused')>
                        <i class="ri-stop-line me-1"></i> Stop this run
                    </button>
                </form>
            @else
                <span class="text-muted">This run is {{ $run->status }}. Create a new Start from Automation for a fresh run.</span>
            @endif
        </div>
        <form method="post" action="{{ route('administrator.ai.automation.runs.target', $run) }}" class="row g-2 align-items-end">
            @csrf
            @method('PUT')
            <div class="col-md-4">
                <label class="form-label">Target (total posts for this run)</label>
                <input type="number" name="target" class="form-control" min="1" max="100000" value="{{ $run->target }}" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Update target</button>
            </div>
        </form>
    </div>
</div>
@endcan

<div class="card panel-card mb-3">
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Generation settings used for this run',
        'cardSubtitle' => 'Captured when the run started (Asia/Kolkata). Live links open current admin pools/templates.',
    ])
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="small text-muted">Claude model</div>
                <div class="fw-semibold">{{ $snapshot['model'] ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Interval</div>
                <div class="fw-semibold">{{ $snapshot['interval_label'] ?? ($snapshot['interval'] ?? $run->interval_snapshot) }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Target total AI posts (global)</div>
                <div class="fw-semibold">{{ $snapshot['target_total'] ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Auto-publish %</div>
                <div class="fw-semibold">{{ $snapshot['publish_percent'] ?? '—' }}%</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Min words</div>
                <div class="fw-semibold">{{ $snapshot['min_words'] ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Max tokens</div>
                <div class="fw-semibold">{{ $snapshot['max_tokens'] ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Default category</div>
                <div class="fw-semibold">{{ $snapshot['default_category'] ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Snapshot captured</div>
                <div class="fw-semibold">{{ $snapshot['captured_at'] ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Categories used in topics</div>
                <div class="fw-semibold">
                    @forelse($categoriesUsed as $cat)
                        <span class="badge bg-soft-secondary text-secondary me-1">{{ $cat->name }}</span>
                    @empty
                        —
                    @endforelse
                </div>
            </div>
            <div class="col-12">
                <div class="small text-muted">Extra system prompt</div>
                <pre class="bg-light border rounded p-2 mb-0 small" style="white-space:pre-wrap">{{ trim((string) ($snapshot['system_prompt_extra'] ?? '')) !== '' ? $snapshot['system_prompt_extra'] : '— (none)' }}</pre>
            </div>
            <div class="col-12">
                <div class="small text-muted">Research / ranking notes</div>
                <pre class="bg-light border rounded p-2 mb-0 small" style="white-space:pre-wrap">{{ trim((string) ($snapshot['research_notes'] ?? '')) !== '' ? $snapshot['research_notes'] : '— (none)' }}</pre>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card panel-card h-100">
            @include('admin.master.partials.panel-card-header-list', [
                'cardTitle' => 'Structure templates',
                'cardSubtitle' => 'Templates available when this run generates',
            ])
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Name</th><th>Key</th><th>Type</th><th>Sections</th><th>Active</th></tr></thead>
                        <tbody>
                        @forelse(($snapshot['structure_templates'] ?? []) as $tpl)
                            <tr>
                                <td>{{ $tpl['name'] }}</td>
                                <td><code>{{ $tpl['key'] }}</code></td>
                                <td>{{ $tpl['content_type'] }}</td>
                                <td>{{ $tpl['sections_count'] }}</td>
                                <td>{{ !empty($tpl['status']) ? 'yes' : 'no' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-3 text-muted">No templates in snapshot.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    <a href="{{ route('administrator.ai.automation.templates') }}">Manage structure templates →</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card panel-card h-100">
            @include('admin.master.partials.panel-card-header-list', [
                'cardTitle' => 'Geo pools & angles',
                'cardSubtitle' => 'Location / angle pools used for auto titles',
            ])
            <div class="card-body">
                @php $geo = $snapshot['geo_pools'] ?? []; @endphp
                <div class="row g-2 mb-3">
                    <div class="col-6"><div class="small text-muted">Zones</div><div class="fw-semibold">{{ $geo['zones'] ?? 0 }}</div></div>
                    <div class="col-6"><div class="small text-muted">Areas</div><div class="fw-semibold">{{ $geo['areas'] ?? 0 }}</div></div>
                    <div class="col-6"><div class="small text-muted">Roads</div><div class="fw-semibold">{{ $geo['roads'] ?? 0 }}</div></div>
                    <div class="col-6"><div class="small text-muted">Landmarks</div><div class="fw-semibold">{{ $geo['landmarks'] ?? 0 }}</div></div>
                    <div class="col-12"><div class="small text-muted">Total geo nodes</div><div class="fw-semibold">{{ $geo['total'] ?? 0 }}</div></div>
                </div>
                <div class="small text-muted mb-1">Angles ({{ count($snapshot['angles'] ?? []) }})</div>
                <div class="d-flex flex-wrap gap-1 mb-3" style="max-height:160px;overflow:auto">
                    @foreach(($snapshot['angles'] ?? []) as $angle)
                        <span class="badge bg-soft-info text-info">{{ $angle['name'] }} ({{ $angle['intent'] }})</span>
                    @endforeach
                </div>
                <a href="{{ route('administrator.ai.automation.geo') }}">Manage geo & angles →</a>
            </div>
        </div>
    </div>
</div>

<div class="card panel-card mb-3">
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Topics', 'cardSubtitle' => 'Planned seeds for this run'])
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Topic</th>
                        <th>Category</th>
                        <th>Geo</th>
                        <th>Angle</th>
                        <th>Status</th>
                        <th>Article</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($topics as $topic)
                    <tr>
                        <td>{{ $topic->title_hint }}</td>
                        <td>{{ $topic->category?->name ?? '—' }}</td>
                        <td>{{ $topic->geoNode?->name }}</td>
                        <td>{{ $topic->angle?->name }}</td>
                        <td>{{ $topic->status }}</td>
                        <td>
                            @if($topic->article_id)
                                <a href="/administrator/articles/{{ $topic->article_id }}/edit">#{{ $topic->article_id }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $topic->updated_at?->timezone($tz)->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-3 text-muted">No topics yet for this run.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($topics->hasPages())
    <div class="card-footer">{{ $topics->links() }}</div>
    @endif
</div>
@endsection
