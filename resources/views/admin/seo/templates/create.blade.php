@extends('admin.layouts.vertical', ['title' => 'Create template'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'New Template', 'back' => route('administrator.seo.templates.index'), 'crumbs' => ['Templates' => route('administrator.seo.templates.index'), 'Create' => null]])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Template'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.seo.templates.store') }}">@csrf
            @include('admin.seo.templates._form')
            <button class="btn btn-primary">Create</button>
        </form>
    </div>
</div>
@endsection
