@extends('admin.layouts.vertical', ['title' => 'Edit role'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Edit Role', 'crumbs' => ['System' => null, 'Roles' => route('administrator.roles.index'), 'Edit' => null], 'back' => route('administrator.roles.index')])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Role Details'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.roles.update', $role) }}">@csrf @method('PUT')
            @include('admin.roles._form')
            <button class="btn btn-primary" type="submit">Update role</button>
        </form>
    </div>
</div>
@endsection
