@extends('admin.layouts.vertical', ['title' => 'Edit template'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Edit Template', 'back' => route('administrator.seo.templates.index'), 'crumbs' => ['Templates' => route('administrator.seo.templates.index'), 'Edit' => null]])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Template'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.seo.templates.update', $template) }}">@csrf @method('PUT')
            @include('admin.seo.templates._form')
            <button class="btn btn-primary">Update</button>
        </form>
    </div>
</div>
@endsection
