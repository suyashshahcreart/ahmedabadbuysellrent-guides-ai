@extends('admin.layouts.vertical', ['title' => 'AI usage'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'AI Usage — '.$user->name,
    'subheading' => 'Remaining this month: '.$remainingCredits,
    'crumbs' => ['AI Credits' => route('administrator.ai.credits.index'), 'Usage' => null],
    'back' => route('administrator.ai.credits.index'),
])
<div class="card panel-card border-primary border-top">
    <div class="card-header"><h5 class="card-title mb-0">Usage history</h5></div>
    <div class="card-body p-0">
        @if($aiUsageHistory->isEmpty())
            <div class="text-center text-muted py-5">No AI usage recorded yet.</div>
        @else
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Action</th><th>Credits</th></tr></thead>
            <tbody>
            @foreach($aiUsageHistory as $row)
                <tr><td>{{ $row->created_at->format('d M Y H:i') }}</td><td>{{ $row->action }}</td><td>{{ $row->credits }}</td></tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
