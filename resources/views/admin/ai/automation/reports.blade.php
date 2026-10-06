@extends('admin.layouts.vertical', ['title' => 'AI Reports'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'AI Automation Reports',
    'subheading' => 'Overall progress, each Start history run, and generated articles.',
    'crumbs' => ['AI' => route('administrator.ai.automation.index'), 'Reports' => null],
    'back' => route('administrator.ai.automation.index'),
])

<div class="row g-3 mb-3">
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Generated</div><div class="fs-4 fw-semibold">{{ $totalAi }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Published</div><div class="fs-4 fw-semibold">{{ $publishedAi }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Drafts</div><div class="fs-4 fw-semibold">{{ $draftAi }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Running now</div><div class="fs-4 fw-semibold">{{ $running }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Remaining</div><div class="fs-4 fw-semibold">{{ $remaining }}</div></div></div></div>
    <div class="col-md-2"><div class="card panel-card"><div class="card-body"><div class="small text-muted">Tokens in/out</div><div class="fs-6 fw-semibold">{{ number_format($tokenIn) }} / {{ number_format($tokenOut) }}</div></div></div></div>
</div>

<div class="card panel-card mb-3">
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Start history (runs)',
        'cardSubtitle' => 'Each Start opens a new run. Older runs stay active until their Target is finished, and keep working in background.',
    ])
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Interval</th>
                        <th>Total / Generated</th>
                        <th>Pending</th>
                        <th>Failed</th>
                        <th>Dupes</th>
                        <th>Running</th>
                        <th>Tokens</th>
                        <th>Started</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($runs as $run)
                    @php
                        $total = (int) $run->target;
                        $generated = (int) $run->completed;
                        $pending = max(0, $total - $generated);
                        $badge = match($run->status) {
                            'running' => 'danger',
                            'active' => 'success',
                            'completed' => 'secondary',
                            'paused' => 'warning',
                            'failed' => 'dark',
                            default => 'light',
                        };
                    @endphp
                    <tr>
                        <td>#{{ $run->id }}</td>
                        <td>
                            <span class="badge bg-{{ $badge }}">{{ $run->status }}</span>
                            @if($settings->active_run_id === $run->id)
                                <span class="badge bg-soft-primary text-primary">current</span>
                            @endif
                        </td>
                        <td>{{ $run->interval_snapshot }}</td>
                        <td>
                            <strong>{{ $total }}/{{ $generated }}</strong>
                            <div class="small text-muted">total / generated</div>
                        </td>
                        <td>
                            <strong>{{ $pending }}</strong>
                            <div class="small text-muted">left</div>
                        </td>
                        <td>{{ $run->failed }}</td>
                        <td>{{ $run->duplicate_skipped }}</td>
                        <td>{{ $run->running }}</td>
                        <td>{{ number_format($run->input_tokens) }} / {{ number_format($run->output_tokens) }}</td>
                        <td>{{ $run->started_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('administrator.ai.automation.runs.show', $run) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="p-3 text-muted">No runs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($runs->hasPages())
    <div class="card-footer">{{ $runs->links() }}</div>
    @endif
</div>

<div class="card panel-card">
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Generated articles',
        'cardSubtitle' => 'Latest AI posts (all runs)',
    ])
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Run</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($recentArticles as $article)
                    <tr>
                        <td>#{{ $article->id }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($article->title, 70) }}</td>
                        <td>
                            @if($article->ai_run_id)
                                <a href="{{ route('administrator.ai.automation.runs.show', $article->ai_run_id) }}">#{{ $article->ai_run_id }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $article->status }}</td>
                        <td>{{ $article->category?->name ?? '—' }}</td>
                        <td>{{ $article->author?->name ?? '—' }}</td>
                        <td>{{ $article->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ url('/administrator/articles/'.$article->id.'/edit') }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-3 text-muted">No AI articles yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
