@php
    $searchId = $searchId ?? 'sortableListSearch';
    $perPageId = $perPageId ?? 'sortableListPerPage';
    $searchPlaceholder = $searchPlaceholder ?? 'Search...';
@endphp
<div class="card-body border-bottom py-3 px-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <label for="{{ $perPageId }}" class="form-label mb-0 text-muted small">Show</label>
            <select id="{{ $perPageId }}" class="form-select form-select-sm" style="width:auto;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-muted small">entries</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="{{ $searchId }}" class="form-label mb-0 text-muted small">Search:</label>
            <input type="search" id="{{ $searchId }}" class="form-control form-control-sm" placeholder="{{ $searchPlaceholder }}" style="min-width:220px;">
        </div>
    </div>
</div>
