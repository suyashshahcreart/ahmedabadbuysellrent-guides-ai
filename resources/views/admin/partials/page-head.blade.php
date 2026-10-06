<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                @foreach($crumbs ?? [] as $label => $url)
                    @if($url)
                        <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                    @else
                        <li class="breadcrumb-item active">{{ $label }}</li>
                    @endif
                @endforeach
            </ol>
        </nav>
        <h3 class="mb-0">{{ $heading }}</h3>
        @if(!empty($subheading))<p class="text-muted mb-0 small">{{ $subheading }}</p>@endif
    </div>
    <div class="d-flex align-items-center gap-2">
        @isset($back)
            <a href="{{ $back }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
        @endisset
        {!! $actions ?? '' !!}
    </div>
</div>
