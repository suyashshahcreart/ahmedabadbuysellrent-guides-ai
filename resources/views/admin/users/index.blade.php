@extends('admin.layouts.vertical', ['title' => 'Users'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'All Users',
    'crumbs' => ['System' => null, 'Users' => null],
    'back' => route('administrator.dashboard'),
    'actions' => auth()->user()->can('users.create') || auth()->user()->can('users.manage')
        ? '<a href="'.route('administrator.users.create').'" class="btn btn-primary"><i class="ri-user-add-line me-1"></i> Add New</a>'
        : '',
])
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', ['cardTitle' => 'All Users', 'cardSubtitle' => 'Manage system users and their assigned roles'])
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Filter by role</label>
                <select class="form-select form-select-sm js-dt-filter" data-table="#users-table" data-column="4">
                    <option value="">All roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" id="users-table" data-search="Search users...">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th class="text-center">Roles count</th>
                        <th>Roles</th>
                        <th>Created At</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->id }}</td>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td class="text-center"><span class="badge bg-soft-secondary text-secondary">{{ $user->roles_count }}</span></td>
                        <td>
                            @forelse($user->roles as $role)
                                <span class="badge bg-soft-primary text-primary">{{ $role->name }}</span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                        <td class="text-nowrap text-muted">{{ optional($user->created_at)->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-soft-info" href="{{ route('administrator.users.show', $user) }}" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
                            @canany(['users.edit','users.manage'])
                            <a class="btn btn-sm btn-soft-primary" href="{{ route('administrator.users.edit', $user) }}" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line"></i></a>
                            @endcanany
                            @canany(['users.delete','users.manage'])
                            <form class="d-inline" method="post" action="{{ route('administrator.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-soft-danger" type="submit" data-bs-toggle="tooltip" title="Delete"><i class="ri-delete-bin-line"></i></button>
                            </form>
                            @endcanany
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
