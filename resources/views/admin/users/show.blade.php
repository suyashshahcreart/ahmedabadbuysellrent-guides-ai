@extends('admin.layouts.vertical', ['title' => 'User'])
@section('content')
@include('admin.partials.page-head', ['heading' => $user->name, 'crumbs' => ['System' => null, 'Users' => route('administrator.users.index'), 'View' => null], 'back' => route('administrator.users.index')])
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <p class="mb-1"><strong>Email</strong><br>{{ $user->email }}</p>
            <p class="mb-0"><strong>Roles</strong><br>
                @foreach($user->roles as $r)<span class="badge bg-soft-primary text-primary">{{ $r->name }}</span> @endforeach
            </p>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card panel-card border-primary border-top" data-panel-card>
            @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Effective permissions'])
            <div class="card-body">
                @foreach($effective as $p)<span class="badge bg-soft-secondary text-secondary mb-1">{{ $p->name }}</span>@endforeach
            </div>
        </div>
    </div>
</div>
@endsection
