@extends('admin.layouts.vertical', ['title' => 'Dashboard'])
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Dashboard</h3>
    <a class="btn btn-primary" href="{{ route('administrator.articles.create') }}">New article</a>
</div>
<div class="row g-3">
    @foreach([
        ['Articles', $articles, 'ri-article-line'],
        ['Published', $published, 'ri-checkbox-circle-line'],
        ['Scheduled', $scheduled, 'ri-time-line'],
        ['Pages', $pages, 'ri-pages-line'],
        ['Media files', $media, 'ri-image-line'],
    ] as $card)
    <div class="col-md-4 col-xl">
        <div class="card">
            <div class="card-body">
                <i class="{{ $card[2] }} fs-24 text-primary"></i>
                <h3 class="mt-2 mb-0">{{ $card[1] }}</h3>
                <p class="text-muted mb-0">{{ $card[0] }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>
<p class="mt-4"><a class="btn btn-soft-primary" href="{{ route('administrator.seo.audit') }}">SEO checklist</a></p>
@endsection
