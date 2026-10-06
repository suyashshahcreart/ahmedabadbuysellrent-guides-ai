@extends('admin.layouts.vertical', ['title' => 'Edit permission'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Edit Permission', 'back' => route('administrator.permissions.index'), 'crumbs' => ['System' => null, 'Permissions' => route('administrator.permissions.index'), 'Edit' => null]])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Permission'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.permissions.update', $permission) }}">@csrf @method('PUT')
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="{{ $permission->name }}" required>
            <button class="btn btn-primary mt-2">Update</button>
        </form>
    </div>
</div>
@endsection
