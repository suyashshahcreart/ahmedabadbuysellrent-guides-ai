@extends('admin.layouts.vertical', ['title' => 'Activity'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Activity',
    'crumbs' => ['Activity' => null],
    'back' => route('administrator.dashboard'),
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Activity log', 'cardSubtitle' => 'Recent administrator actions'])
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="activity-table" data-search="Search activity...">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td class="text-nowrap text-muted">{{ optional($log->created_at)->format('M d, Y H:i') }}</td>
                        <td>{{ $log->description }}</td>
                        <td>{{ optional($log->causer)->email ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
