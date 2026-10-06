@php
    $infoId = $infoId ?? 'sortableListInfo';
    $paginationId = $paginationId ?? 'sortableListPagination';
@endphp
<div class="card-body border-top py-3 px-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="text-muted small" id="{{ $infoId }}"></div>
        <nav aria-label="List pagination">
            <ul class="pagination pagination-sm mb-0" id="{{ $paginationId }}"></ul>
        </nav>
    </div>
</div>
