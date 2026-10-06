<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $meta['title'] ?? $siteName }}</title>
  <meta name="description" content="{{ $meta['description'] ?? '' }}">
  <link rel="canonical" href="{{ $meta['canonical'] ?? url('/') }}">
  <meta name="robots" content="{{ $meta['robots'] ?? 'index, follow' }}">
  <meta property="og:title" content="{{ $meta['og_title'] ?? $meta['title'] ?? $siteName }}">
  <meta property="og:description" content="{{ $meta['og_description'] ?? $meta['description'] ?? '' }}">
  <meta property="og:url" content="{{ $meta['canonical'] ?? url('/') }}">
  <meta property="og:type" content="{{ $ogType ?? 'website' }}">
  <meta property="og:site_name" content="{{ $siteName }}">
  @if(!empty($meta['og_image']))
  <meta property="og:image" content="{{ $meta['og_image'] }}">
  @endif
  <meta name="twitter:card" content="{{ !empty($meta['og_image']) ? 'summary_large_image' : 'summary' }}">
  <meta name="twitter:title" content="{{ $meta['twitter_title'] ?? $meta['title'] ?? '' }}">
  <meta name="twitter:description" content="{{ $meta['twitter_description'] ?? $meta['description'] ?? '' }}">
  @if(!empty($meta['twitter_image']))
  <meta name="twitter:image" content="{{ $meta['twitter_image'] }}">
  @endif
  <link rel="icon" href="{{ $siteFavicon ?? '/favicon.svg' }}">
  <link rel="shortcut icon" href="{{ $siteFavicon ?? '/favicon.svg' }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,500&amp;family=Source+Sans+3:ital,wght@0,400;0,600;0,700;1,400&amp;display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/site.css?v=11">
  @if(!empty($jsonLdBlocks))
    @foreach($jsonLdBlocks as $block)
  <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach
  @elseif(isset($jsonLd))
  <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
  @endif
  @yield('head')
</head>
<body class="@yield('bodyClass')">
  @include('public.partials.header')
  <main>
    @yield('content')
  </main>
  @include('public.partials.footer')
  @include('public.partials.site-popup')
  <script src="/js/site.js?v=7" defer></script>
</body>
</html>
