@extends('admin.layouts.vertical', ['title' => 'Create user'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Create New User', 'crumbs' => ['System' => null, 'Users' => route('administrator.users.index'), 'Create' => null], 'back' => route('administrator.users.index')])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'User Details', 'cardSubtitle' => 'Provide basic information and assign roles'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.users.store') }}">
            @csrf
            @include('admin.users._form', ['user' => null])
            <button class="btn btn-primary" type="submit">Create user</button>
        </form>
    </div>
</div>
@endsection
