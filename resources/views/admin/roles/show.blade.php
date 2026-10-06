@extends('admin.layouts.vertical', ['title' => 'Role'])
@section('content')
@include('admin.partials.page-head', ['heading' => $role->name, 'crumbs' => ['System' => null, 'Roles' => route('administrator.roles.index'), 'View' => null], 'back' => route('administrator.roles.index')])
<div class="card panel-card border-primary border-top mb-3" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Permissions'])
    <div class="card-body">@foreach($role->permissions as $p)<span class="badge bg-soft-info text-info mb-1">{{ $p->name }}</span> @endforeach</div>
</div>
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Users'])
    <table class="table mb-0">@foreach($role->users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td></tr>@endforeach</table>
</div>
@endsection
