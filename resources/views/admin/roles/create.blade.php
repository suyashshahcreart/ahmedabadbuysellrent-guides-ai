@extends('admin.layouts.vertical', ['title' => 'Create role'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Create New Role', 'crumbs' => ['System' => null, 'Roles' => route('administrator.roles.index'), 'Create' => null], 'back' => route('administrator.roles.index')])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Role Details'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.roles.store') }}">@csrf
            @include('admin.roles._form', ['role' => null])
            <button class="btn btn-primary" type="submit">Create role</button>
        </form>
    </div>
</div>
@endsection
