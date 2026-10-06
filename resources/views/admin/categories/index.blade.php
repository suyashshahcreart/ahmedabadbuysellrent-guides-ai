@extends('admin.layouts.vertical', ['title' => 'Categories'])

@section('css')
<style>
.cat-sortable .sortable-handle { cursor: grab; }
.cat-sortable .sortable-handle:active { cursor: grabbing; }
.cat-sortable .sortable-ghost { opacity: 0.5; background: #eef3ff !important; }
.cat-sortable .sortable-ghost td { background: #eef3ff !important; }
.cat-sortable .sortable-chosen td { background: #f8f9fa; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Categories</li>
                    </ol>
                </nav>
                <h3 class="mb-0">Categories</h3>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('administrator.dashboard') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
                @canany(['categories.create','categories.manage'])
                <a href="{{ route('administrator.categories.create') }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i> New Category
                </a>
                @endcanany
            </div>
        </div>

        <div class="card panel-card border-primary border-top" data-panel-card>
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-folder-line me-2 text-primary"></i>Guide Categories</h5>
                    <p class="card-text text-muted small mb-0">Drag rows to reorder. Active categories appear in navigation and filters.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Filter by status">
                        <a href="{{ route('administrator.categories.index', ['status' => 'all']) }}"
                           class="btn {{ ($status ?? 'all') === 'all' ? 'btn-primary' : 'btn-soft-secondary' }}">All</a>
                        <a href="{{ route('administrator.categories.index', ['status' => 'active']) }}"
                           class="btn {{ ($status ?? 'all') === 'active' ? 'btn-success' : 'btn-soft-success' }}">Active</a>
                        <a href="{{ route('administrator.categories.index', ['status' => 'inactive']) }}"
                           class="btn {{ ($status ?? 'all') === 'inactive' ? 'btn-danger' : 'btn-soft-danger' }}">Inactive</a>
                    </div>
                    <span class="badge bg-soft-primary text-primary" id="catCount">{{ $categories->count() }} categories</span>
                    @include('admin.master.partials.panel-actions-list')
                </div>
            </div>
            <div class="card-body p-0">
                @if($categories->isNotEmpty())
                @include('admin.partials.sortable-list-toolbar', [
                    'searchId' => 'catSearch',
                    'perPageId' => 'catPerPage',
                    'searchPlaceholder' => 'Search categories...',
                ])
                @endif
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;"></th>
                                <th style="width:60px;" class="text-center">SR</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Parent</th>
                                <th class="text-center">Guides</th>
                                <th class="text-center">Active</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="catSortable" class="cat-sortable">
                            @forelse($categories as $index => $cat)
                            @php $isActive = $cat->status === 'published'; @endphp
                            <tr data-id="{{ $cat->id }}"
                                data-is-active="{{ $isActive ? '1' : '0' }}"
                                data-search="{{ strtolower($cat->name.' '.$cat->slug.' '.($cat->parent?->name ?? '')) }}">
                                <td class="text-center">
                                    @canany(['categories.edit','categories.manage'])
                                    <span class="sortable-handle text-muted" title="Drag to reorder">
                                        <i class="ri-draggable fs-5"></i>
                                    </span>
                                    @endcanany
                                </td>
                                <td class="text-center text-muted small fw-semibold cat-sr-no">{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $cat->name }}</td>
                                <td><code class="small">{{ $cat->slug }}</code></td>
                                <td class="text-muted small">{{ $cat->parent?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-soft-secondary text-secondary">{{ (int) $cat->articles_count }}</span>
                                </td>
                                <td class="text-center cat-status-cell">
                                    @if($isActive)
                                        <span class="badge bg-soft-success text-success">Active</span>
                                    @else
                                        <span class="badge bg-soft-danger text-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @canany(['categories.edit','categories.manage'])
                                        <button type="button"
                                                class="btn btn-sm {{ $isActive ? 'btn-outline-warning' : 'btn-outline-success' }} btn-toggle-category-active"
                                                data-url="{{ route('administrator.categories.toggle-status', $cat) }}"
                                                data-is-active="{{ $isActive ? '1' : '0' }}"
                                                data-name="{{ $cat->name }}"
                                                title="{{ $isActive ? 'Lock' : 'Unlock' }}">
                                            <i class="{{ $isActive ? 'ri-lock-line' : 'ri-lock-unlock-line' }}"></i>
                                        </button>
                                        <a href="{{ route('administrator.categories.edit', $cat) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        @endcanany
                                        @canany(['categories.delete','categories.manage'])
                                        <form method="POST" action="{{ route('administrator.categories.destroy', $cat) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-master"
                                                    data-item-name="{{ $cat->name }}" title="Delete">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                        @endcanany
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr data-empty-static>
                                <td colspan="8" class="text-center text-muted py-4">No categories yet.
                                    @canany(['categories.create','categories.manage'])
                                    <a href="{{ route('administrator.categories.create') }}">Create one</a>.
                                    @endcanany
                                </td>
                            </tr>
                            @endforelse
                            <tr id="catNoResults" class="d-none" data-empty-row>
                                <td colspan="8" class="text-center text-muted py-4">No matching categories found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if($categories->isNotEmpty())
                @include('admin.partials.sortable-list-footer', [
                    'infoId' => 'catListInfo',
                    'paginationId' => 'catPagination',
                ])
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
@include('admin.partials.sortable-list-ui-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
(function () {
    var tbody      = document.getElementById('catSortable');
    var reorderUrl = @json(route('administrator.categories.reorder'));
    var csrfToken  = @json(csrf_token());
    var listFilter = @json($status ?? 'all');
    var listUi     = window.initSortableListUi({
        tbodyId: 'catSortable',
        searchId: 'catSearch',
        perPageId: 'catPerPage',
        infoId: 'catListInfo',
        paginationId: 'catPagination',
        emptyRowId: 'catNoResults',
        countBadgeId: 'catCount',
        itemPlural: 'categories',
    });
    var statusEl   = document.createElement('span');
    statusEl.className = 'badge bg-soft-secondary text-secondary ms-2 d-none';
    statusEl.id = 'catReorderStatus';
    var cardHeader = document.getElementById('catCount');
    if (cardHeader) cardHeader.parentNode.insertBefore(statusEl, cardHeader);

    function showStatus(msg, type) {
        statusEl.textContent = msg;
        statusEl.className = 'badge ms-2 bg-soft-' + type + ' text-' + type;
        statusEl.classList.remove('d-none');
        if (type !== 'secondary') {
            setTimeout(function () { statusEl.classList.add('d-none'); }, 2500);
        }
    }

    function updateToggleButton(btn, isActive) {
        btn.dataset.isActive = isActive ? '1' : '0';
        btn.title = isActive ? 'Lock' : 'Unlock';
        btn.classList.remove('btn-outline-warning', 'btn-outline-success');
        btn.classList.add(isActive ? 'btn-outline-warning' : 'btn-outline-success');
        var icon = btn.querySelector('i');
        if (icon) icon.className = isActive ? 'ri-lock-line' : 'ri-lock-unlock-line';
    }

    function updateStatusBadge(cell, isActive) {
        if (!cell) return;
        cell.innerHTML = isActive
            ? '<span class="badge bg-soft-success text-success">Active</span>'
            : '<span class="badge bg-soft-danger text-danger">Inactive</span>';
    }

    function performToggle(btn) {
        btn.disabled = true;
        fetch(btn.dataset.url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) throw new Error('Toggle failed');
            var row = btn.closest('tr');
            var isActive = !!res.is_active;
            if (row) {
                row.dataset.isActive = isActive ? '1' : '0';
                updateStatusBadge(row.querySelector('.cat-status-cell'), isActive);
                if ((listFilter === 'active' && !isActive) || (listFilter === 'inactive' && isActive)) {
                    row.style.transition = 'opacity .25s ease';
                    row.style.opacity = '0';
                    setTimeout(function () { row.remove(); listUi.refresh(); }, 250);
                }
            }
            updateToggleButton(btn, isActive);
            listUi.refresh();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: isActive ? 'Category unlocked' : 'Category locked', text: res.message || 'Status updated.', timer: 1800, showConfirmButton: false });
            } else {
                showStatus(res.message || 'Updated', 'success');
            }
        })
        .catch(function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Update failed', text: 'Could not update category status.' });
            } else {
                showStatus('Could not update status', 'danger');
            }
        })
        .finally(function () { btn.disabled = false; });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-toggle-category-active');
        if (!btn || btn.disabled) return;
        e.preventDefault();
        var isActive = btn.dataset.isActive === '1';
        var name = btn.dataset.name || 'this category';
        if (typeof Swal === 'undefined') {
            if (window.confirm((isActive ? 'Lock' : 'Unlock') + ' "' + name + '"?')) performToggle(btn);
            return;
        }
        Swal.fire({
            title: isActive ? 'Lock category?' : 'Unlock category?',
            html: 'Are you sure you want to <strong>' + (isActive ? 'lock' : 'unlock') + '</strong> <em>' + name + '</em>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isActive ? 'Yes, lock' : 'Yes, unlock',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
            customClass: { confirmButton: (isActive ? 'btn btn-warning' : 'btn btn-success') + ' me-2', cancelButton: 'btn btn-outline-secondary' },
            buttonsStyling: false,
        }).then(function (result) { if (result.isConfirmed) performToggle(btn); });
    });

    if (!tbody || typeof Sortable === 'undefined') return;

    new Sortable(tbody, {
        handle: '.sortable-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function () {
            var ids = [];
            tbody.querySelectorAll('tr[data-id]').forEach(function (row, idx) {
                ids.push(parseInt(row.dataset.id, 10));
                var srCell = row.querySelector('.cat-sr-no');
                if (srCell) srCell.textContent = idx + 1;
            });
            showStatus('Saving…', 'secondary');
            fetch(reorderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ ids: ids }),
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                showStatus(res.success ? 'Saved ✓' : 'Error saving', res.success ? 'success' : 'danger');
                if (res.success) listUi.refresh();
            })
            .catch(function () { showStatus('Error saving', 'danger'); });
        },
    });
})();
</script>
@endsection
