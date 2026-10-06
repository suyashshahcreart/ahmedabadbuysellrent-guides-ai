@extends('admin.layouts.vertical', ['title' => $title])

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">{{ $title }}</li>
            </ol>
        </nav>
        <h3 class="mb-0">{{ $title }}</h3>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('administrator.dashboard') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
        @if(!empty($createUrl))
        <a class="btn btn-primary" href="{{ $createUrl }}"><i class="ri-add-line me-1"></i> {{ $createLabel ?? 'Add New' }}</a>
        @endif
    </div>
</div>

<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-list', [
        'cardTitle' => $cardTitle ?? $title,
        'cardSubtitle' => $cardSubtitle ?? ('Manage '.strtolower($title)),
    ])
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 js-datatable" data-search="Search {{ strtolower($title) }}...">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        @if(!empty($showCount))
                        <th class="text-center">{{ $countLabel ?? 'Items' }}</th>
                        @endif
                        @if(!empty($showSort))
                        <th>Sort</th>
                        @endif
                        <th>Status</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['label'] }}</td>
                        <td class="text-muted"><code class="small">{{ $row['slug'] ?? '—' }}</code></td>
                        @if(!empty($showCount))
                        <td class="text-center"><span class="badge bg-soft-secondary text-secondary">{{ (int) ($row['count'] ?? 0) }}</span></td>
                        @endif
                        @if(!empty($showSort))
                        <td>{{ (int) ($row['sort'] ?? 0) }}</td>
                        @endif
                        <td>
                            @if(!empty($row['active']))
                                <span class="badge bg-soft-success text-success">{{ $row['status_label'] ?? 'Active' }}</span>
                            @else
                                <span class="badge bg-soft-danger text-danger">{{ $row['status_label'] ?? 'Inactive' }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                @if(!empty($row['edit']))
                                <a href="{{ $row['edit'] }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="ri-edit-line"></i></a>
                                @endif
                                @if(!empty($row['delete']))
                                <form method="POST" action="{{ $row['delete'] }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-master"
                                            data-item-name="{{ $row['label'] }}" title="Delete">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No items found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
