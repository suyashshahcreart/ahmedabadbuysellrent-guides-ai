@if ($paginator->hasPages())
<nav class="admin-pagination" role="navigation" aria-label="Pagination">
    <p class="admin-pagination__info text-muted mb-0">
        Showing
        <span class="fw-semibold text-body">{{ $paginator->firstItem() }}</span>
        to
        <span class="fw-semibold text-body">{{ $paginator->lastItem() }}</span>
        of
        <span class="fw-semibold text-body">{{ $paginator->total() }}</span>
        results
    </p>
    <ul class="pagination pagination-rounded pagination-sm mb-0">
        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            @if ($paginator->onFirstPage())
                <span class="page-link" aria-hidden="true"><i class="ri-arrow-left-s-line"></i></span>
            @else
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"><i class="ri-arrow-left-s-line"></i></a>
            @endif
        </li>
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
            @if ($paginator->hasMorePages())
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"><i class="ri-arrow-right-s-line"></i></a>
            @else
                <span class="page-link" aria-hidden="true"><i class="ri-arrow-right-s-line"></i></span>
            @endif
        </li>
    </ul>
</nav>
@elseif(isset($paginator) && method_exists($paginator, 'total') && $paginator->total() > 0)
    <p class="text-muted small mb-0">Showing {{ $paginator->total() }} {{ $paginator->total() === 1 ? 'result' : 'results' }}</p>
@endif
