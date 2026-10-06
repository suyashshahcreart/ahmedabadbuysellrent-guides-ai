@extends('admin.layouts.vertical', ['title' => 'Tags'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Tags</li>
                    </ol>
                </nav>
                <h3 class="mb-0">Tags</h3>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('administrator.dashboard') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
                @canany(['tags.create','tags.manage'])
                <a href="{{ route('administrator.tags.create') }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i> New Tag
                </a>
                @endcanany
            </div>
        </div>

        <div class="card panel-card border-primary border-top" data-panel-card>
            @include('admin.master.partials.panel-card-header-list', [
                'cardTitle' => 'Guide Tags',
                'cardSubtitle' => 'Manage tags for guide classification',
            ])
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 js-datatable" id="tagsTable" data-search="Search tags...">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Sort</th>
                                <th class="text-center">Guides</th>
                                <th>Status</th>
                                <th class="text-end no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($tags as $tag)
                            @php $active = ($tag->status ?? 'published') === 'published'; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $tag->name }}</td>
                                <td class="text-muted"><code class="small">{{ $tag->slug }}</code></td>
                                <td>{{ (int) ($tag->sort_order ?? 0) }}</td>
                                <td class="text-center"><span class="badge bg-soft-secondary text-secondary">{{ (int) $tag->articles_count }}</span></td>
                                <td>
                                    @if($active)
                                        <span class="badge bg-soft-success text-success">Active</span>
                                    @else
                                        <span class="badge bg-soft-danger text-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @canany(['tags.edit','tags.manage'])
                                        <a href="{{ route('administrator.tags.edit', $tag) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        @endcanany
                                        @canany(['tags.delete','tags.manage'])
                                        <form method="POST" action="{{ route('administrator.tags.destroy', $tag) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-master"
                                                    data-item-name="{{ $tag->name }}" title="Delete">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                        @endcanany
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
