@extends('admin.layouts.vertical', ['title' => 'AI Automation'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'AI Blog Automation',
    'subheading' => 'Schedule Claude-generated Ahmedabad guides with uniqueness checks and reports.',
    'crumbs' => ['AI' => null, 'Automation' => null],
    'actions' => auth()->user()->can('ai.automation.manage')
        ? '<a href="'.route('administrator.ai.automation.reports').'" class="btn btn-outline-secondary me-1">Reports</a>'
        : '<a href="'.route('administrator.ai.automation.reports').'" class="btn btn-outline-secondary">Reports</a>',
])

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card panel-card"><div class="card-body"><div class="text-muted small">AI articles</div><div class="fs-3 fw-semibold">{{ $totalAi }} / {{ $settings->target_total }}</div></div></div></div>
    <div class="col-md-3"><div class="card panel-card"><div class="card-body"><div class="text-muted small">Running now</div><div class="fs-3 fw-semibold">{{ $running }}</div></div></div></div>
    <div class="col-md-3"><div class="card panel-card"><div class="card-body"><div class="text-muted small">Failed / duplicates</div><div class="fs-3 fw-semibold">{{ $failed }} / {{ $duplicates }}</div></div></div></div>
    <div class="col-md-3"><div class="card panel-card"><div class="card-body"><div class="text-muted small">Status</div><div class="fs-5 fw-semibold">{{ $settings->enabled ? 'Enabled' : 'Paused' }}</div><div class="small text-muted">Next: {{ $settings->next_tick_at?->format('Y-m-d H:i:s') ?? '—' }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card panel-card">
            @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Schedule & model', 'cardSubtitle' => 'Each Start opens a new run. Every interval, each active run (target not finished) can generate 1 post in background.'])
            <div class="card-body">
                <form method="post" action="{{ route('administrator.ai.automation.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Interval</label>
                            <select name="interval" class="form-select" @disabled(!auth()->user()->can('ai.automation.manage'))>
                                @foreach(\App\Models\AiSetting::intervalOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected($settings->interval === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target total AI posts</label>
                            <input type="number" name="target_total" class="form-control" value="{{ old('target_total', $settings->target_total) }}" min="1" @disabled(!auth()->user()->can('ai.automation.manage'))>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Auto-publish %</label>
                            <input type="number" name="publish_percent" class="form-control" value="{{ old('publish_percent', $settings->publish_percent) }}" min="0" max="100" @disabled(!auth()->user()->can('ai.automation.manage'))>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Min words</label>
                            <input type="number" name="min_words" class="form-control" value="{{ old('min_words', $settings->min_words) }}" @disabled(!auth()->user()->can('ai.automation.manage'))>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max tokens</label>
                            <input type="number" name="max_tokens" class="form-control" value="{{ old('max_tokens', $settings->max_tokens) }}" @disabled(!auth()->user()->can('ai.automation.manage'))>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Claude model</label>
                            <input type="text" name="model" class="form-control" value="{{ old('model', $settings->model) }}" @disabled(!auth()->user()->can('ai.automation.manage'))>
                            <div class="form-text">Cheap default: claude-haiku-4-5-20251001</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default category</label>
                            <select name="default_category_id" class="form-select" @disabled(!auth()->user()->can('ai.automation.manage'))>
                                <option value="">—</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected($settings->default_category_id == $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Extra system prompt</label>
                            <textarea name="system_prompt_extra" class="form-control" rows="3" @disabled(!auth()->user()->can('ai.automation.manage'))>{{ old('system_prompt_extra', $settings->system_prompt_extra) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Research / ranking notes</label>
                            <textarea name="research_notes" class="form-control" rows="3" @disabled(!auth()->user()->can('ai.automation.manage'))>{{ old('research_notes', $settings->research_notes) }}</textarea>
                        </div>
                    </div>
                    @can('ai.automation.manage')
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit">Save settings</button>
                    </div>
                    @endcan
                </form>

                @can('ai.automation.manage')
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <form method="post" action="{{ route('administrator.ai.automation.start') }}">
                        @csrf
                        <button class="btn btn-success" type="submit">Start (first post now)</button>
                    </form>
                    <form method="post" action="{{ route('administrator.ai.automation.pause') }}">
                        @csrf
                        <button class="btn btn-warning" type="submit">Pause</button>
                    </form>
                    <form method="post" action="{{ route('administrator.ai.automation.generate-once') }}">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit">Generate one now (queue)</button>
                    </form>
                </div>
                @endcan
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card panel-card mb-3">
            @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Pools & tools', 'cardSubtitle' => 'Geo, templates, editors'])
            <div class="card-body d-grid gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('administrator.ai.automation.templates') }}">Structure templates</a>
                <a class="btn btn-outline-secondary" href="{{ route('administrator.ai.automation.geo') }}">Geo & angles</a>
                <a class="btn btn-outline-secondary" href="{{ route('administrator.ai.automation.editors') }}">Editors</a>
                @can('ai.automation.manage')
                <form method="post" action="{{ route('administrator.ai.automation.seed-pools') }}">@csrf<button class="btn btn-outline-dark" type="submit">Re-seed pools</button></form>
                @endcan
                <div class="small text-muted">Requires <code>ANTHROPIC_API_KEY</code> in .env and <code>queue:work --queue=ai-generate</code>.</div>
            </div>
        </div>
        <div class="card panel-card">
            @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Recent generations', 'cardSubtitle' => 'Latest log rows'])
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Status</th><th>Topic</th><th>Tokens</th></tr></thead>
                        <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td>{{ $log->status }}</td>
                                <td>
                                    @if($log->article_id)
                                        <a href="/administrator/articles/{{ $log->article_id }}/edit">#{{ $log->article_id }}</a>
                                    @else
                                        {{ \Illuminate\Support\Str::limit($log->prompt_summary, 40) }}
                                    @endif
                                </td>
                                <td>{{ $log->input_tokens }} / {{ $log->output_tokens }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted p-3">No generations yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
