@props([
    'homeUrl' => null,
    'wrapperClass' => 'mx-auto mb-4 text-center auth-logo',
])
@php
    $site = $site ?? \App\Support\SiteBranding::forView();
    $link = $homeUrl ?? url('/');
    $h = (int) ($site['auth']['height_px'] ?? 32);
    $showName = ! empty($site['show_name']);
@endphp
<div class="{{ $wrapperClass }}">
    <a href="{{ $link }}" class="logo-dark d-inline-flex align-items-center justify-content-center gap-2 text-decoration-none">
        <img src="{{ $site['auth']['dark'] }}" height="{{ $h }}" alt="{{ $site['name'] }}">
        @if($showName)
        <span class="fw-bold text-dark">{{ $site['name'] }}</span>
        @endif
    </a>
    <a href="{{ $link }}" class="logo-light d-inline-flex align-items-center justify-content-center gap-2 text-decoration-none">
        <img src="{{ $site['auth']['light'] }}" height="{{ $h }}" alt="{{ $site['name'] }}">
        @if($showName)
        <span class="fw-bold text-white">{{ $site['name'] }}</span>
        @endif
    </a>
</div>
