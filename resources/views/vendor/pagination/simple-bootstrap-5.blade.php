@if ($paginator->hasPages())
<nav class="admin-pagination" role="navigation" aria-label="Pagination">
    <ul class="pagination pagination-rounded pagination-sm mb-0">
        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            @if ($paginator->onFirstPage())
                <span class="page-link"><i class="ri-arrow-left-s-line"></i></span>
            @else
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="ri-arrow-left-s-line"></i></a>
            @endif
        </li>
        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
            @if ($paginator->hasMorePages())
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="ri-arrow-right-s-line"></i></a>
            @else
                <span class="page-link"><i class="ri-arrow-right-s-line"></i></span>
            @endif
        </li>
    </ul>
</nav>
@endif
