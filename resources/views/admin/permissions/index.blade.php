@extends('admin.layouts.vertical', ['title' => 'Permissions'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'All Permissions',
    'crumbs' => ['System' => null, 'Permissions' => null],
    'back' => route('administrator.dashboard'),
    'actions' => auth()->user()->can('permissions.create') ? '<a class="btn btn-primary" href="'.route('administrator.permissions.create').'"><i class="ri-add-line me-1"></i> Add New</a>' : '',
])
<div class="alert alert-warning">Renaming a permission breaks Blade authorization checks in the administrator sidebar until you update those gates.</div>
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => 'Permissions List',
        'cardSubtitle' => 'Manage permissions available throughout the system',
    ])
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="permissions-table" data-search="Search permissions...">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Guard</th>
                        <th class="text-center">Assigned Roles</th>
                        <th class="text-center">Assigned Users</th>
                        <th>Created At</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($permissions as $permission)
                    <tr>
                        <td class="fw-semibold">{{ $permission->id }}</td>
                        <td>{{ $permission->name }}</td>
                        <td><span class="badge bg-soft-primary text-primary">{{ $permission->guard_name }}</span></td>
                        <td class="text-center"><span class="badge bg-soft-primary text-primary">{{ $permission->roles_count }}</span></td>
                        <td class="text-center"><span class="badge bg-soft-primary text-primary">{{ $permission->users_count }}</span></td>
                        <td class="text-nowrap text-muted">{{ optional($permission->created_at)->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-soft-info" href="{{ route('administrator.permissions.show', $permission) }}" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
                            @can('permissions.edit')<a class="btn btn-sm btn-soft-primary" href="{{ route('administrator.permissions.edit', $permission) }}" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line"></i></a>@endcan
                            @can('permissions.delete')
                            <form class="d-inline" method="post" action="{{ route('administrator.permissions.destroy', $permission) }}" onsubmit="return confirm('Delete this permission?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-soft-danger" type="submit" data-bs-toggle="tooltip" title="Delete"><i class="ri-delete-bin-line"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
