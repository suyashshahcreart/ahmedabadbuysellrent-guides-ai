@extends('admin.layouts.vertical', ['title' => 'Sitemap Manager'])
@section('content')
@include('admin.partials.page-head', [
    'heading' => 'Sitemap Manager',
    'crumbs' => ['SEO Management' => null, 'Sitemap' => null],
    'actions' => '<a href="'.$sitemapIndex.'" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="ri-external-link-line me-1"></i> View sitemap.xml</a>
        <form method="POST" action="'.route('administrator.seo.sitemap.rebuild').'" class="d-inline">'.csrf_field().'<button class="btn btn-soft-primary btn-sm"><i class="ri-refresh-line me-1"></i> Rebuild Now</button></form>
        <form method="POST" action="'.route('administrator.seo.sitemap.clear').'" class="d-inline">'.csrf_field().'<button class="btn btn-soft-warning btn-sm"><i class="ri-delete-bin-line me-1"></i> Clear Cache</button></form>',
])
<div class="alert alert-soft-info mb-3">Cache driver: <strong>{{ strtoupper($cacheDriver) }}</strong>@if($lastRebuild) · Last rebuild {{ $lastRebuild }}@endif</div>
<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('administrator.seo.sitemap.update') }}">
            @csrf
            <div class="card mb-3">
                <div class="card-header fw-semibold">What to include in Google sitemap</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" @checked($enabled)>
                        <label class="form-check-label fw-semibold">Sitemap enabled</label>
                    </div>
                    @foreach($includes as $type => $on)
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="include_{{ $type }}" value="1" @checked($on)>
                        <label class="form-check-label text-capitalize">Include {{ $type }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header fw-semibold">Static pages</div>
                <div class="card-body">
                    @for($i = 0; $i < max(3, count($staticPages)); $i++)
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><input class="form-control" name="static[{{ $i }}][loc]" placeholder="https://…" value="{{ $staticPages[$i]['loc'] ?? '' }}"></div>
                        <div class="col-md-3">
                            <select class="form-select" name="static[{{ $i }}][changefreq]">
                                @foreach(['always','hourly','daily','weekly','monthly','yearly','never'] as $freq)
                                    <option value="{{ $freq }}" @selected(($staticPages[$i]['changefreq'] ?? 'weekly') === $freq)>{{ $freq }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><input class="form-control" name="static[{{ $i }}][priority]" placeholder="0.5" value="{{ $staticPages[$i]['priority'] ?? '' }}"></div>
                    </div>
                    @endfor
                    <button class="btn btn-primary">Save settings</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold">Live sitemap files</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @forelse($files as $file)
                        <li class="mb-2"><a href="{{ $file }}" target="_blank">{{ $file }}</a></li>
                    @empty
                        <li class="text-muted">Sitemap is disabled.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
