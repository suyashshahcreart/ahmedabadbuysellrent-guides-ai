@extends('admin.layouts.vertical', ['title' => 'Permission'])
@section('content')
@include('admin.partials.page-head', ['heading' => $permission->name, 'back' => route('administrator.permissions.index'), 'crumbs' => ['System' => null, 'Permissions' => route('administrator.permissions.index'), 'View' => null]])
<div class="card mb-3"><div class="card-body">
    <h5>Roles</h5>
    @forelse($roles as $r)<span class="badge bg-soft-primary text-primary">{{ $r->name }}</span> @empty <span class="text-muted">None</span> @endforelse
    <h5 class="mt-3">Direct users</h5>
    @forelse($users as $u)<div>{{ $u->email }}</div> @empty <span class="text-muted">None</span> @endforelse
</div></div>
@endsection
