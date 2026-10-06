@extends('admin.layouts.vertical', ['title' => 'Add redirect'])
@section('content')
@include('admin.partials.page-head', ['heading' => 'Add redirect', 'back' => route('administrator.redirects.index'), 'crumbs' => ['URL Redirects' => route('administrator.redirects.index'), 'Create' => null]])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Redirect'])
    <div class="card-body">
        <form method="POST" action="{{ route('administrator.redirects.store') }}">@csrf
            <div class="mb-3">
                <label class="form-label">From path</label>
                <input class="form-control" name="from_path" placeholder="/old-url/" required>
            </div>
            <div class="mb-3">
                <label class="form-label">To URL</label>
                <input class="form-control" name="to_url" placeholder="/new-url/" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status_code">
                    <option value="301">301 Permanent</option>
                    <option value="302">302 Temporary</option>
                </select>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="active" value="1" checked>
                <label class="form-check-label">Active</label>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <input class="form-control" name="notes">
            </div>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
