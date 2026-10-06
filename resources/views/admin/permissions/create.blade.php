@extends('admin.layouts.vertical', ['title' => 'Create permission'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Create Permission', 'back' => route('administrator.permissions.index'), 'crumbs' => ['System' => null, 'Permissions' => route('administrator.permissions.index'), 'Create' => null]])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Permission'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.permissions.store') }}">@csrf
            <label class="form-label">Name</label>
            <input class="form-control" name="name" required placeholder="module.action">
            <p class="form-text">Use dotted Guides names (e.g. articles.publish). Changing names later breaks sidebar gates.</p>
            <button class="btn btn-primary mt-2">Create</button>
        </form>
    </div>
</div>
@endsection
