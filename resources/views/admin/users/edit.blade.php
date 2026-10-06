@extends('admin.layouts.vertical', ['title' => 'Edit user'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Edit User', 'crumbs' => ['System' => null, 'Users' => route('administrator.users.index'), 'Edit' => null], 'back' => route('administrator.users.index')])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'User Details'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.users.update', $user) }}">
            @csrf @method('PUT')
            @include('admin.users._form', ['user' => $user])
            <button class="btn btn-primary" type="submit">Update user</button>
        </form>
    </div>
</div>
@endsection
