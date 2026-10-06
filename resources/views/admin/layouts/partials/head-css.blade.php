<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@yield('css')
@vite(['resources/scss/icons.scss','resources/scss/app.scss'])
@vite(['resources/js/admin-config.js'])
@if(is_array($site ?? null))
    <style id="theme-branding-vars">
        :root {
            --bs-logo-lg-height: {{ $site['logo_lg_height'] ?? '28px' }};
            --bs-logo-sm-height: {{ $site['logo_sm_height'] ?? '22px' }};
        }
        @if(! empty($site['main_nav_bg']))
        .wrapper {
            --bs-main-nav-bg: {{ $site['main_nav_bg'] }} !important;
        }
        @endif
        .logo-box .logo-brand {
            display: inline-flex !important;
            align-items: center;
            gap: .55rem;
            max-width: 100%;
            height: auto !important;
            vertical-align: middle;
            text-decoration: none;
        }
        .logo-box .logo-brand-img {
            height: var(--bs-logo-lg-height, 30px);
            width: auto;
            flex-shrink: 0;
            display: block;
        }
        .logo-box .logo-brand-text {
            font-size: .95rem;
            font-weight: 700;
            line-height: 1.15;
            color: var(--bs-main-nav-item-active-color, #eef2f7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 170px;
        }
        html[data-menu-color="light"] .logo-box .logo-brand-text {
            color: #1c252e;
        }
        html[data-menu-size="condensed"] .logo-box .logo-brand {
            display: none !important;
        }
    </style>
@endif
@if(is_array($site ?? null) && ! empty($site['theme_mode_css']))
    <style id="theme-mode-overrides">
        {!! $site['theme_mode_css'] !!}
    </style>
@endif
@stack('head')
