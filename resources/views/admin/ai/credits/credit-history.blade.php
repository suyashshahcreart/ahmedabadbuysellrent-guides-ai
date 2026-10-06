@extends('admin.layouts.vertical', ['title' => 'Credit history'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Credit History — '.$user->name,
    'subheading' => $user->email.' · Current limit: '.$user->ai_monthly_credit.' credits/month',
    'crumbs' => ['AI Credits' => route('administrator.ai.credits.index'), 'History' => null],
    'back' => route('administrator.ai.credits.index'),
])
<div class="card panel-card border-primary border-top">
    <div class="card-header"><h5 class="card-title mb-0">Admin Credit Adjustments</h5></div>
    <div class="card-body p-0">
        @if($creditHistory->isEmpty())
            <div class="text-center text-muted py-5">No credit adjustments yet.</div>
        @else
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Old</th><th>New</th><th>Change</th><th>Forced</th><th>Note</th><th>Actor</th></tr></thead>
            <tbody>
            @foreach($creditHistory as $row)
                @php $diff = $row->new_credit - $row->old_credit; @endphp
                <tr>
                    <td>{{ $row->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $row->old_credit }}</td>
                    <td>{{ $row->new_credit }}</td>
                    <td><span class="badge {{ $diff >= 0 ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">{{ $diff >= 0 ? '+' : '' }}{{ $diff }}</span></td>
                    <td>{{ $row->forced ? 'Yes' : '—' }}</td>
                    <td>{{ $row->note ?: '—' }}</td>
                    <td>{{ optional($row->actor)->name }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
