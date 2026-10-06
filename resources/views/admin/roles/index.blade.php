@extends('admin.layouts.vertical', ['title' => 'Roles'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'All Roles',
    'crumbs' => ['System' => null, 'Roles' => null],
    'back' => route('administrator.dashboard'),
    'actions' => auth()->user()->can('roles.create') ? '<a class="btn btn-primary" href="'.route('administrator.roles.create').'"><i class="ri-add-line me-1"></i> Add New</a>' : '',
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'Roles List', 'cardSubtitle' => 'Roles and assigned permissions'])
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="roles-table" data-search="Search roles...">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th class="text-center">Users</th>
                        <th>Permissions</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td class="fw-semibold">{{ $role->id }}</td>
                        <td class="fw-semibold">{{ $role->name }}</td>
                        <td class="text-center"><span class="badge bg-soft-secondary text-secondary">{{ $role->users_count }}</span></td>
                        <td>
                            @foreach($role->permissions->take(6) as $p)<span class="badge bg-soft-info text-info">{{ $p->name }}</span> @endforeach
                            @if($role->permissions->count() > 6)<span class="text-muted small">+{{ $role->permissions->count() - 6 }}</span>@endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-soft-info" href="{{ route('administrator.roles.show', $role) }}" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
                            @can('roles.edit')<a class="btn btn-sm btn-soft-primary" href="{{ route('administrator.roles.edit', $role) }}" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line"></i></a>@endcan
                            @can('roles.delete')
                            <form class="d-inline" method="post" action="{{ route('administrator.roles.destroy', $role) }}" onsubmit="return confirm('Delete role?')">@csrf @method('DELETE')
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
