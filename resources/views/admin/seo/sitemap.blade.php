@extends('admin.layouts.vertical', ['title' => 'Sitemap'])

@section('content')
<h3 class="mb-3">Sitemap</h3>
<div class="card panel-card border-primary border-top" data-panel-card>
    @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Sitemap settings'])
    <div class="card-body">
        <form method="post" action="{{ route('administrator.seo.sitemap.rebuild') }}">
            @csrf
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="sitemap_enabled" value="1" id="sitemap_enabled" @checked($enabled)>
                <label class="form-check-label" for="sitemap_enabled">Sitemap enabled (public /sitemap.xml)</label>
            </div>
            <p class="text-muted">Last rebuild marker: {{ $lastRebuild ?: 'never' }}</p>
            <ul>
                @foreach($files as $f)
                    <li><a href="{{ $f }}" target="_blank" rel="noopener">{{ $f }}</a></li>
                @endforeach
            </ul>
            <button class="btn btn-primary" type="submit">Save &amp; mark rebuilt</button>
        </form>
    </div>
</div>
@endsection
