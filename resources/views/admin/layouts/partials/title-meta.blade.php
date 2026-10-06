@php
    $site = $site ?? \App\Support\SiteBranding::forView();
    $appName = $site['name'];
    $favicon = $site['favicon'];
@endphp
<meta charset="utf-8" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'Admin' }} | {{ $appName }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<link rel="icon" href="{{ $favicon }}">
<link rel="shortcut icon" href="{{ $favicon }}">
