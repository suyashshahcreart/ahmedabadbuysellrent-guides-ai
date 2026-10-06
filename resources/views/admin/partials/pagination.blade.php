@php
    $paginator = $paginator ?? $items ?? null;
@endphp
@if($paginator)
<div class="admin-list-footer">
    {{ $paginator->onEachSide(1)->links() }}
</div>
@endif
