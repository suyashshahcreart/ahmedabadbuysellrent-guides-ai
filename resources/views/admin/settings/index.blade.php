@extends('admin.layouts.vertical', ['title' => 'Settings', 'subTitle' => 'System'])

@section('css')
    <style>
        .settings-page .list-group-item { border: 1px solid var(--bs-border-color); }
        .settings-page .list-group-item.active { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
        .settings-page .list-group-item.active i { color: inherit; }
        .settings-page .platform-settings { display: none; }
        .settings-page .platform-settings.active { display: block; }
        .settings-asset-card {
            border: 0;
            box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.06);
            height: 100%;
        }
        .settings-asset-card .settings-asset-preview-wrap {
            position: relative;
            background: var(--bs-tertiary-bg, #f8f9fa);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius, 0.375rem);
            min-height: 10rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .settings-asset-card .settings-asset-preview-wrap .media-select-area { min-height: 10rem; width: 100%; }
        .settings-asset-card .media-preview { max-width: 100%; object-fit: contain; }
        .settings-asset-card .media-placeholder { padding: 0.5rem; }
    </style>
@endsection

@section('content')
    <div class="row settings-page">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <nav aria-label="breadcrumb" class="mb-1">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('administrator.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">System</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Settings</li>
                        </ol>
                    </nav>
                    <h3 class="mb-0">General settings</h3>
                </div>
                <a href="{{ route('administrator.dashboard') }}" class="btn btn-soft-secondary"><i class="ri-arrow-go-back-line me-1"></i>Back</a>
            </div>

            <div class="card panel-card border-primary border-top" data-panel-card>
                <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-1">Site, SMS, mail, theme &amp; posts</h4>
                        <p class="text-muted mb-0">Website title, brand images, MSG91 SMS, Laravel mail variables, CSS theme overrides, editor autosave timing, and the public-site popup.</p>
                    </div>
                    <div class="panel-actions d-flex gap-2">
                        <button type="button" class="btn btn-light border" data-panel-action="refresh" title="Refresh" aria-label="Refresh" onclick="window.location.reload()">
                            <i class="ri-refresh-line"></i>
                        </button>
                        <button type="button" class="btn btn-light border" data-panel-action="collapse" title="Collapse">
                            <i class="ri-arrow-up-s-line"></i>
                        </button>
                        <button type="button" class="btn btn-light border" data-panel-action="fullscreen" title="Fullscreen">
                            <i class="ri-fullscreen-line"></i>
                        </button>
                        <button type="button" class="btn btn-light border" data-panel-action="close" title="Close" onclick="window.location.href='{{ route('administrator.dashboard') }}'">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $dp = $settingsDefaultPlatform ?? 'sitesinfo';
                        $tabCount = (int) (! empty($canSettingsSiteInfo)) + (int) (! empty($canSettingsSms)) + (int) (! empty($canSettingsMail)) + (int) (! empty($canSettingsThemeMode)) + (int) (! empty($canSettingsPost)) + (int) (! empty($canSettingsPopup));
                    @endphp
                    @if($tabCount === 0)
                    <div class="alert alert-info mb-0" role="status">
                        You can open this page, but no settings sections are enabled for your account. An administrator can grant <strong>Site info</strong>, <strong>SMS configuration</strong>, <strong>Mail configuration</strong>, <strong>Theme mode</strong>, and/or <strong>Post settings</strong> permissions.
                    </div>
                    @else
                    <div class="row g-3">
                        @if($tabCount > 1)
                        <div class="col-md-4 col-lg-3">
                            <div class="list-group">
                                @if(! empty($canSettingsSiteInfo))
                                <a href="#" class="list-group-item list-group-item-action {{ $dp === 'sitesinfo' ? 'active' : '' }}" data-platform="sitesinfo" role="tab" aria-selected="{{ $dp === 'sitesinfo' ? 'true' : 'false' }}">
                                    <i class="ri-palette-line me-2"></i>Site info
                                </a>
                                @endif
                                @if(! empty($canSettingsSms))
                                <a href="#" class="list-group-item list-group-item-action {{ $dp === 'smsConfig' ? 'active' : '' }}" data-platform="smsConfig" role="tab" aria-selected="{{ $dp === 'smsConfig' ? 'true' : 'false' }}">
                                    <i class="ri-message-3-line me-2"></i>SMS configuration
                                </a>
                                @endif
                                @if(! empty($canSettingsMail))
                                <a href="#" class="list-group-item list-group-item-action {{ $dp === 'mailConfig' ? 'active' : '' }}" data-platform="mailConfig" role="tab" aria-selected="{{ $dp === 'mailConfig' ? 'true' : 'false' }}">
                                    <i class="ri-mail-settings-line me-2"></i>Mail configuration
                                </a>
                                @endif
                                @if(! empty($canSettingsThemeMode))
                                <a href="#" class="list-group-item list-group-item-action {{ $dp === 'themeMode' ? 'active' : '' }}" data-platform="themeMode" role="tab" aria-selected="{{ $dp === 'themeMode' ? 'true' : 'false' }}">
                                    <i class="ri-contrast-2-line me-2"></i>Theme mode
                                </a>
                                @endif
                                @if(! empty($canSettingsPost))
                                <a href="#" class="list-group-item list-group-item-action {{ $dp === 'postSettings' ? 'active' : '' }}" data-platform="postSettings" role="tab" aria-selected="{{ $dp === 'postSettings' ? 'true' : 'false' }}">
                                    <i class="ri-article-line me-2"></i>Post settings
                                </a>
                                @endif
                                @if(! empty($canSettingsPopup))
                                <a href="#" class="list-group-item list-group-item-action {{ $dp === 'popupSettings' ? 'active' : '' }}" data-platform="popupSettings" role="tab" aria-selected="{{ $dp === 'popupSettings' ? 'true' : 'false' }}">
                                    <i class="ri-window-line me-2"></i>Popup settings
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="{{ $tabCount > 1 ? 'col-md-8 col-lg-9' : 'col-12' }}">
                            @if(! empty($canSettingsSiteInfo))
                            <div class="platform-settings {{ $dp === 'sitesinfo' ? 'active' : '' }}" id="sitesinfo-settings">
                                <form id="siteInfoSettingForm" method="POST" action="{{ route('administrator.settings.store') }}">
                                    @csrf
                                    <h6 class="mb-3 d-flex align-items-center gap-2 text-primary">
                                        <i class="ri-image-2-line"></i> Site title, logo &amp; icon
                                    </h6>
                                    <div class="mb-3">
                                        <label for="websitetitle-url" class="form-label">Website title</label>
                                        <input type="text" class="form-control" name="websitetitle" id="websitetitle-url"
                                            placeholder="Website title"
                                            value="{{ $settings['websitetitle'] ?? '' }}">
                                    </div>
                                    <div class="row g-3 mb-2">
                                        <div class="col-md-4">
                                            <label for="logo-lg-height" class="form-label">Sidebar large logo height</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="logo_lg_height" id="logo-lg-height"
                                                    min="12" max="200" step="1"
                                                    value="{{ old('logo_lg_height', $settings['logo_lg_height'] ?? '30') }}"
                                                    placeholder="30">
                                                <span class="input-group-text">px</span>
                                            </div>
                                            <p class="form-text mb-0">CSS <code>--bs-logo-lg-height</code> (expanded sidebar / large logo).</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="logo-sm-height" class="form-label">Sidebar small logo height</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="logo_sm_height" id="logo-sm-height"
                                                    min="12" max="200" step="1"
                                                    value="{{ old('logo_sm_height', $settings['logo_sm_height'] ?? '28') }}"
                                                    placeholder="28">
                                                <span class="input-group-text">px</span>
                                            </div>
                                            <p class="form-text mb-0">CSS <code>--bs-logo-sm-height</code> (collapsed / small logo).</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="main-nav-bg" class="form-label">Main navigation (sidebar) background</label>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <input type="text" class="form-control" name="main_nav_bg" id="main-nav-bg"
                                                    style="max-width: 10rem"
                                                    value="{{ old('main_nav_bg', $settings['main_nav_bg'] ?? '') }}"
                                                    placeholder="e.g. #eeeef3"
                                                    pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$"
                                                    title="Hex color (#RGB, #RRGGBB) or leave empty for theme default"
                                                    autocomplete="off">
                                                <input type="color" class="form-control form-control-color" id="main-nav-bg-picker"
                                                    value="{{ !empty($settings['main_nav_bg']) && preg_match('/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/', (string)($settings['main_nav_bg'] ?? '')) ? $settings['main_nav_bg'] : '#eeeef3' }}"
                                                    title="Pick color, then copy into the field or type a hex value">
                                            </div>
                                            <p class="form-text mb-0">Sets CSS <code>--bs-main-nav-bg</code> for <code>.wrapper .main-nav</code>. Leave empty to use the theme default (light/dark menu presets).</p>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-2">
                                        <div class="col-md-4">
                                            <label for="auth-logo-height" class="form-label">Auth pages logo height</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="auth_logo_height" id="auth-logo-height"
                                                    min="12" max="200" step="1"
                                                    value="{{ old('auth_logo_height', $settings['auth_logo_height'] ?? '32') }}"
                                                    placeholder="32">
                                                <span class="input-group-text">px</span>
                                            </div>
                                            <p class="form-text mb-0">Login, register, password reset, lock screen, and similar auth layouts (<code>height</code> on both <code>.logo-dark</code> and <code>.logo-light</code> images).</p>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        
                                        <div class="col-12">
                                            <h6 class="text-muted text-uppercase small mb-2 mt-1">Sidebar (logo box)</h6>
                                            <p class="text-muted small mb-3">Four images like <code>.logo-box</code>: <span class="text-dark">.logo-dark</span> uses small + large dark; <span class="text-dark">.logo-light</span> uses small + large light. Leave empty to use the theme defaults (same as <code>public/images</code>).</p>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-moon-line me-1 text-primary"></i> Dark — small (logo-sm)</h6>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 8rem;">
                                                                <img src="{{ $settings['sidebar_logo_dark_sm_url'] ?? '' }}" class="img-fluid media-preview" alt="Dark sm" style="max-height: 40px; max-width: 100%; display: {{ !empty($settings['sidebar_logo_dark_sm_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['sidebar_logo_dark_sm_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">Default: logo-sm</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['sidebar_logo_dark_sm_id']) ? 'Change' : 'From library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['sidebar_logo_dark_sm_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="sidebar_logo_dark_sm_id" class="media-input" value="{{ $settings['sidebar_logo_dark_sm_id'] ?? '' }}">
                                                        <input type="hidden" name="sidebar_logo_dark_sm_url" class="media-url-input" value="{{ $settings['sidebar_logo_dark_sm_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-moon-line me-1 text-primary"></i> Dark — large (logo-lg)</h6>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 8rem;">
                                                                <img src="{{ $settings['sidebar_logo_dark_lg_url'] ?? '' }}" class="img-fluid media-preview" alt="Dark lg" style="max-height: 40px; max-width: 100%; display: {{ !empty($settings['sidebar_logo_dark_lg_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['sidebar_logo_dark_lg_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">Default: logo-dark</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['sidebar_logo_dark_lg_id']) ? 'Change' : 'From library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['sidebar_logo_dark_lg_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="sidebar_logo_dark_lg_id" class="media-input" value="{{ $settings['sidebar_logo_dark_lg_id'] ?? '' }}">
                                                        <input type="hidden" name="sidebar_logo_dark_lg_url" class="media-url-input" value="{{ $settings['sidebar_logo_dark_lg_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-sun-line me-1 text-primary"></i> Light — small (logo-sm)</h6>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 8rem;">
                                                                <img src="{{ $settings['sidebar_logo_light_sm_url'] ?? '' }}" class="img-fluid media-preview" alt="Light sm" style="max-height: 40px; max-width: 100%; display: {{ !empty($settings['sidebar_logo_light_sm_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['sidebar_logo_light_sm_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">Default: logo-sm</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['sidebar_logo_light_sm_id']) ? 'Change' : 'From library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['sidebar_logo_light_sm_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="sidebar_logo_light_sm_id" class="media-input" value="{{ $settings['sidebar_logo_light_sm_id'] ?? '' }}">
                                                        <input type="hidden" name="sidebar_logo_light_sm_url" class="media-url-input" value="{{ $settings['sidebar_logo_light_sm_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-sun-line me-1 text-primary"></i> Light — large (logo-lg)</h6>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 8rem;">
                                                                <img src="{{ $settings['sidebar_logo_light_lg_url'] ?? '' }}" class="img-fluid media-preview" alt="Light lg" style="max-height: 40px; max-width: 100%; display: {{ !empty($settings['sidebar_logo_light_lg_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['sidebar_logo_light_lg_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">Default: logo-light</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['sidebar_logo_light_lg_id']) ? 'Change' : 'From library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['sidebar_logo_light_lg_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="sidebar_logo_light_lg_id" class="media-input" value="{{ $settings['sidebar_logo_light_lg_id'] ?? '' }}">
                                                        <input type="hidden" name="sidebar_logo_light_lg_url" class="media-url-input" value="{{ $settings['sidebar_logo_light_lg_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-link-m me-1 text-primary"></i> Favicon</h6>
                                                    <p class="text-muted small mb-0">Browser tab icon</p>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 10rem;">
                                                                <img src="{{ $settings['favicon_image_url'] ?? '' }}" class="img-fluid media-preview" alt="Favicon preview" style="max-height: 72px; max-width: 100%; display: {{ !empty($settings['favicon_image_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['favicon_image_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">No image selected</span>
                                                                    <span class="d-block text-muted" style="font-size:0.7rem;">Choose from media library</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['favicon_image_id']) ? 'Change image' : 'From media library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['favicon_image_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="favicon_image_id" class="media-input" value="{{ $settings['favicon_image_id'] ?? '' }}">
                                                        <input type="hidden" name="favicon_image_url" class="media-url-input" value="{{ $settings['favicon_image_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <h6 class="text-muted text-uppercase small mb-2 mt-1">Auth pages (login, register, reset)</h6>
                                            <p class="text-muted small mb-3">On auth screens, <span class="text-dark">.logo-dark</span> shows on the default (light) auth background; <span class="text-dark">.logo-light</span> shows when the page uses dark theme (<code>data-bs-theme=&quot;dark&quot;</code>). Set two logos for contrast, or use a single fallback image below.</p>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-moon-line me-1 text-primary"></i> Auth — .logo-dark</h6>
                                                    <p class="text-muted small mb-0">Dark logo on light auth background</p>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 10rem;">
                                                                <img src="{{ $settings['auth_logo_dark_url'] ?? '' }}" class="img-fluid media-preview" alt="Auth logo dark" style="max-height: 64px; max-width: 100%; display: {{ !empty($settings['auth_logo_dark_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['auth_logo_dark_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">Uses single fallback or sidebar dark large</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['auth_logo_dark_id']) ? 'Change image' : 'From media library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['auth_logo_dark_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="auth_logo_dark_id" class="media-input" value="{{ $settings['auth_logo_dark_id'] ?? '' }}">
                                                        <input type="hidden" name="auth_logo_dark_url" class="media-url-input" value="{{ $settings['auth_logo_dark_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-sun-line me-1 text-primary"></i> Auth — .logo-light</h6>
                                                    <p class="text-muted small mb-0">Light / inverse logo on dark auth background</p>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 10rem;">
                                                                <img src="{{ $settings['auth_logo_light_url'] ?? '' }}" class="img-fluid media-preview" alt="Auth logo light" style="max-height: 64px; max-width: 100%; display: {{ !empty($settings['auth_logo_light_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['auth_logo_light_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">Uses single fallback or sidebar light large</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['auth_logo_light_id']) ? 'Change image' : 'From media library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['auth_logo_light_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="auth_logo_light_id" class="media-input" value="{{ $settings['auth_logo_light_id'] ?? '' }}">
                                                        <input type="hidden" name="auth_logo_light_url" class="media-url-input" value="{{ $settings['auth_logo_light_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card settings-asset-card h-100">
                                                <div class="card-header py-2 border-bottom">
                                                    <h6 class="mb-0 fs-6"><i class="ri-lock-password-line me-1 text-primary"></i> Single image for both</h6>
                                                    <p class="text-muted small mb-0">Optional legacy shortcut</p>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="js-settings-media">
                                                        <div class="settings-asset-preview-wrap p-2">
                                                            <div class="featured-image-preview w-100 text-center p-2 rounded border border-light border-dashed bg-white media-select-area" style="cursor: pointer; min-height: 10rem;">
                                                                <img src="{{ $settings['admin_image_url'] ?? '' }}" class="img-fluid media-preview" alt="Admin logo" style="max-height: 64px; max-width: 100%; display: {{ !empty($settings['admin_image_url']) ? 'inline-block' : 'none' }};">
                                                                <div class="featured-image-placeholder text-muted small media-placeholder py-3" style="display: {{ !empty($settings['admin_image_url']) ? 'none' : 'block' }};">
                                                                    <i class="ri-image-2-line fs-36 d-block mb-2 text-muted"></i>
                                                                    <span class="d-block">Used for both slots when auth images above are empty</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                                            <button type="button" class="btn btn-primary btn-sm media-select-btn">
                                                                <i class="ri-folders-line me-1"></i> {{ !empty($settings['admin_image_id']) ? 'Change image' : 'From media library' }}
                                                            </button>
                                                            <button type="button" class="btn btn-soft-danger btn-sm remove-image-btn {{ !empty($settings['admin_image_id']) ? '' : 'd-none' }}"><i class="ri-close-line me-1"></i>Remove</button>
                                                        </div>
                                                        <input type="hidden" name="admin_image_id" class="media-input" value="{{ $settings['admin_image_id'] ?? '' }}">
                                                        <input type="hidden" name="admin_image_url" class="media-url-input" value="{{ $settings['admin_image_url'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 justify-content-end mt-4">
                                        <div id="siteInfoAlert" class="flex-grow-1 small" role="status" aria-live="polite"></div>
                                        <button type="submit" class="btn btn-primary"><i class="ri-save-3-line me-1"></i>Save site settings</button>
                                    </div>
                                </form>
                            </div>
                            @endif

                            @if(! empty($canSettingsSms))
                            <div class="platform-settings {{ $dp === 'smsConfig' ? 'active' : '' }}" id="smsConfig-settings">
                                <form id="smsMsg91SettingForm" method="POST" action="{{ route('administrator.settings.store') }}" autocomplete="on">
                                    @csrf
                                    <input type="hidden" name="active_sms_gateway" value="msg91">
                                    <div class="visually-hidden" aria-hidden="true">
                                        <input type="text" id="msg91_form_ignored_username" name="msg91_ignored_username" value="msg91" autocomplete="username" tabindex="-1" readonly>
                                    </div>
                                    <div class="card border shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">MSG91</h6>
                                            <div class="form-check form-switch mb-0">
                                                <input type="hidden" name="sms_gateway_msg91_status" value="0">
                                                <input class="form-check-input" type="checkbox" name="sms_gateway_msg91_status" id="sms_gateway_msg91_status" value="1"
                                                    {{ !empty($settings['sms_gateway_msg91_status']) && (string) $settings['sms_gateway_msg91_status'] === '1' ? 'checked' : '' }} aria-label="Enable MSG91">
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small mb-3">Flow API v5. Credentials and template IDs are stored in site settings (not a config file).</p>
                                            <div class="mb-3">
                                                <label class="form-label" for="msg91_auth_key">MSG91 auth key <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" name="msg91_auth_key" id="msg91_auth_key" autocomplete="new-password" value="{{ $settings['msg91_auth_key'] ?? '' }}" placeholder="From MSG91 dashboard">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="msg91_sender_id">MSG91 sender ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="msg91_sender_id" id="msg91_sender_id" value="{{ $settings['msg91_sender_id'] ?? '' }}" placeholder="e.g. LHOMS">
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label" for="msg91_timeout">Timeout (seconds)</label>
                                                <input type="number" class="form-control" name="msg91_timeout" id="msg91_timeout" min="5" max="120" step="1" value="{{ $settings['msg91_timeout'] ?? '30' }}">
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnMsg91ManageTemplates" data-bs-toggle="modal" data-bs-target="#msg91TemplatesModal">
                                                <i class="ri-file-list-3-line me-1"></i> Manage templates
                                            </button>
                                            <div class="d-flex align-items-center gap-2">
                                                <div id="smsFormAlert" class="small" style="min-width: 120px;" role="status"></div>
                                                <button type="submit" class="btn btn-sm btn-primary"><i class="ri-save-3-line me-1"></i> Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @endif

                            @if(! empty($canSettingsMail))
                            <div class="platform-settings {{ $dp === 'mailConfig' ? 'active' : '' }}" id="mailConfig-settings">
                                @php
                                    $mail = $mailEnv ?? [];
                                    $mailEnc = strtolower((string) ($mail['MAIL_ENCRYPTION'] ?? ''));
                                    if ($mailEnc === 'null') {
                                        $mailEnc = '';
                                    }
                                @endphp
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <p class="text-muted small mb-0">Send a quick test using the mail settings currently loaded by the app (save mail settings first if you edited them above).</p>
                                    <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#mailTestModal" id="btnOpenMailTestModal">
                                        <i class="ri-mail-send-line me-1"></i> Send test mail
                                    </button>
                                </div>
                                <form id="mailEnvSettingForm" method="POST" action="{{ route('administrator.settings.mail') }}" autocomplete="off">
                                    @csrf
                                    <div class="card border shadow-sm">
                                        <div class="card-header">
                                            <h6 class="mb-0">Mail configuration</h6>
                                            <p class="text-muted small mb-0 mt-1">These fields are stored in the settings database and applied at runtime (the <code>.env</code> file is not rewritten).</p>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_mailer">MAIL_MAILER</label>
                                                    <select class="form-select" name="mail_mailer" id="mail_mailer" required>
                                                        @foreach (['log' => 'log', 'array' => 'array', 'smtp' => 'smtp', 'sendmail' => 'sendmail', 'mailgun' => 'mailgun', 'ses' => 'ses', 'postmark' => 'postmark', 'resend' => 'resend', 'failover' => 'failover', 'roundrobin' => 'roundrobin'] as $val => $label)
                                                            <option value="{{ $val }}" @selected(old('mail_mailer', strtolower((string) ($mail['MAIL_MAILER'] ?? 'log'))) === $val)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_encryption">MAIL_ENCRYPTION</label>
                                                    <select class="form-select" name="mail_encryption" id="mail_encryption">
                                                        <option value="" @selected(old('mail_encryption', $mailEnc) === '')>None (empty)</option>
                                                        <option value="tls" @selected(old('mail_encryption', $mailEnc) === 'tls')>tls</option>
                                                        <option value="ssl" @selected(old('mail_encryption', $mailEnc) === 'ssl')>ssl</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_host">MAIL_HOST</label>
                                                    <input type="text" class="form-control" name="mail_host" id="mail_host" value="{{ old('mail_host', $mail['MAIL_HOST'] ?? '') }}" placeholder="127.0.0.1" autocomplete="off">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_port">MAIL_PORT</label>
                                                    <input type="number" class="form-control" name="mail_port" id="mail_port" min="1" max="65535" step="1" value="{{ old('mail_port', $mail['MAIL_PORT'] ?? '') }}" placeholder="2525" autocomplete="off">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_username">MAIL_USERNAME</label>
                                                    <input type="text" class="form-control" name="mail_username" id="mail_username" value="{{ old('mail_username', $mail['MAIL_USERNAME'] ?? '') }}" placeholder="SMTP username or leave empty" autocomplete="off">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_password">MAIL_PASSWORD</label>
                                                    <input type="password" class="form-control" name="mail_password" id="mail_password" value="" placeholder="Leave blank to keep current value" autocomplete="new-password">
                                                    <p class="form-text mb-0 small">Current password is never shown. Submit empty to leave it unchanged.</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_from_address">MAIL_FROM_ADDRESS <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" name="mail_from_address" id="mail_from_address" required value="{{ old('mail_from_address', $mail['MAIL_FROM_ADDRESS'] ?? '') }}" placeholder="hello@example.com" autocomplete="off">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="mail_from_name">MAIL_FROM_NAME <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="mail_from_name" id="mail_from_name" required value="{{ old('mail_from_name', $mail['MAIL_FROM_NAME'] ?? '') }}" placeholder="${APP_NAME} or display name" autocomplete="off">
                                                    <p class="form-text mb-0 small">You may use a literal from-name as shown in outgoing mail.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div id="mailEnvAlert" class="small flex-grow-1" style="min-width: 120px;" role="status"></div>
                                            <button type="submit" class="btn btn-sm btn-primary"><i class="ri-save-3-line me-1"></i> Save mail settings</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @endif

                            @if(! empty($canSettingsThemeMode))
                            <div class="platform-settings {{ $dp === 'themeMode' ? 'active' : '' }}" id="themeMode-settings">
                                <form id="themeModeSettingForm" method="POST" action="{{ route('administrator.settings.store') }}">
                                    @csrf
                                    <h6 class="mb-3 d-flex align-items-center gap-2 text-primary">
                                        <i class="ri-paint-brush-line"></i> Sidebar background (global override)
                                    </h6>
                                    <p class="text-muted small mb-3">Same option as <strong>Site info</strong> — sets <code>--bs-main-nav-bg</code> on <code>.wrapper .main-nav</code> when set.</p>
                                    <div class="mb-4">
                                        <label for="theme-mode-main-nav-bg" class="form-label">Main navigation (sidebar) background</label>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <input type="text" class="form-control" name="main_nav_bg" id="theme-mode-main-nav-bg"
                                                style="max-width: 10rem"
                                                value="{{ old('main_nav_bg', $settings['main_nav_bg'] ?? '') }}"
                                                placeholder="e.g. #eeeef3"
                                                pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$"
                                                title="Hex color (#RGB, #RRGGBB) or leave empty for theme default"
                                                autocomplete="off">
                                            <input type="color" class="form-control form-control-color" id="theme-mode-main-nav-bg-picker"
                                                value="{{ !empty($settings['main_nav_bg']) && preg_match('/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/', (string)($settings['main_nav_bg'] ?? '')) ? $settings['main_nav_bg'] : '#eeeef3' }}"
                                                title="Pick color">
                                        </div>
                                    </div>

                                    @foreach($themeModeFieldGroups as $group)
                                    <div class="card border shadow-sm mb-3">
                                        <div class="card-header py-2">
                                            <h6 class="mb-0">{{ $group['title'] }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                @foreach($group['fields'] as $optionKey => $tuple)
                                                    @php
                                                        $cssProp = $tuple[1];
                                                        $defaultVal = $tuple[2];
                                                        $isDim = str_contains($cssProp, 'height')
                                                            || str_contains($cssProp, 'width')
                                                            || str_contains($cssProp, '-size')
                                                            || str_contains($cssProp, 'padding')
                                                            || str_contains($cssProp, 'margin');
                                                        $stored = old($optionKey, $settings[$optionKey] ?? '');
                                                        $slug = preg_replace('/^--bs-/', '', $cssProp);
                                                        $human = ucwords(str_replace('-', ' ', $slug));
                                                        $allowHexPicker = ! $isDim && stripos($defaultVal, 'rgba') === false;
                                                    @endphp
                                                    <div class="{{ $isDim ? 'col-md-6 col-lg-4' : 'col-md-6' }}">
                                                        <label class="form-label" for="theme-field-{{ $optionKey }}">{{ $human }} (<code>{{ $cssProp }}</code>)</label>
                                                        @if($isDim)
                                                            @php $dimNum = preg_replace('/[^\d.]/', '', (string) $stored); @endphp
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="{{ $optionKey }}" id="theme-field-{{ $optionKey }}"
                                                                    min="0" max="9999" step="1"
                                                                    value="{{ $dimNum !== '' ? $dimNum : '' }}"
                                                                    placeholder="">
                                                                <span class="input-group-text">px</span>
                                                            </div>
                                                            <p class="form-text mb-0 small">Default: <code>{{ $defaultVal }}</code></p>
                                                        @else
                                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                                <input type="text" class="form-control" name="{{ $optionKey }}" id="theme-field-{{ $optionKey }}"
                                                                    style="max-width: 14rem"
                                                                    value="{{ $stored }}"
                                                                    placeholder="{{ $defaultVal }}"
                                                                    @if($allowHexPicker)
                                                                        pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$|^rgba\([0-9,\s.]+\)$"
                                                                    @endif
                                                                    title="{{ $allowHexPicker ? 'Hex or rgba()' : 'rgba() color' }}"
                                                                    autocomplete="off">
                                                                @if($allowHexPicker)
                                                                    <input type="color" class="form-control form-control-color theme-mode-hex-picker" data-target-id="theme-field-{{ $optionKey }}"
                                                                        value="{{ !empty($stored) && preg_match('/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/', (string) $stored) ? $stored : (preg_match('/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$/', $defaultVal) ? $defaultVal : '#604ae3') }}"
                                                                        title="Pick hex color">
                                                                @endif
                                                            </div>
                                                            <p class="form-text mb-0 small">Default: <code>{{ $defaultVal }}</code></p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach

                                    <div class="d-flex flex-wrap align-items-center gap-2 justify-content-end mt-4">
                                        <div id="themeModeAlert" class="flex-grow-1 small" role="status" aria-live="polite"></div>
                                        <button type="submit" class="btn btn-primary"><i class="ri-save-3-line me-1"></i>Save theme settings</button>
                                    </div>
                                </form>
                            </div>
                            @endif

                            @if(! empty($canSettingsPost))
                            @php
                                $postSettings = $postEditorSettings ?? app(\App\Services\Admin\PostEditorSettingsService::class)->allSeconds();
                            @endphp
                            <div class="platform-settings {{ $dp === 'postSettings' ? 'active' : '' }}" id="postSettings-settings">
                                <form id="postSettingsSettingForm" method="POST" action="{{ route('administrator.settings.store') }}">
                                    @csrf
                                    <h6 class="mb-3 d-flex align-items-center gap-2 text-primary">
                                        <i class="ri-article-line"></i> Blog &amp; case study editor
                                    </h6>
                                    <p class="text-muted small mb-3">Controls autosave delay, edit-lock heartbeat, and post list lock refresh for blog posts and case studies.</p>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="post-autosave-debounce" class="form-label">Auto-save delay</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="post_autosave_debounce_seconds" id="post-autosave-debounce"
                                                    min="5" max="300" step="1"
                                                    value="{{ old('post_autosave_debounce_seconds', $postSettings['post_autosave_debounce_seconds'] ?? 30) }}">
                                                <span class="input-group-text">seconds</span>
                                            </div>
                                            <p class="form-text mb-0">Wait time after the last change before saving a draft (5–300).</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="post-lock-heartbeat" class="form-label">Edit lock heartbeat</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="post_lock_heartbeat_seconds" id="post-lock-heartbeat"
                                                    min="5" max="120" step="1"
                                                    value="{{ old('post_lock_heartbeat_seconds', $postSettings['post_lock_heartbeat_seconds'] ?? 10) }}">
                                                <span class="input-group-text">seconds</span>
                                            </div>
                                            <p class="form-text mb-0">How often the editor renews its lock while you are editing (5–120).</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="post-lock-poll" class="form-label">Takeover check interval</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="post_lock_poll_seconds" id="post-lock-poll"
                                                    min="3" max="60" step="1"
                                                    value="{{ old('post_lock_poll_seconds', $postSettings['post_lock_poll_seconds'] ?? 5) }}">
                                                <span class="input-group-text">seconds</span>
                                            </div>
                                            <p class="form-text mb-0">How often the editor checks if another user took over (3–60).</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="post-list-lock-refresh" class="form-label">Post list lock refresh</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="post_list_lock_refresh_seconds" id="post-list-lock-refresh"
                                                    min="5" max="120" step="1"
                                                    value="{{ old('post_list_lock_refresh_seconds', $postSettings['post_list_lock_refresh_seconds'] ?? 15) }}">
                                                <span class="input-group-text">seconds</span>
                                            </div>
                                            <p class="form-text mb-0">How often blog/case study lists refresh lock badges (5–120).</p>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 justify-content-end mt-4">
                                        <div id="postSettingsAlert" class="flex-grow-1 small" role="status" aria-live="polite"></div>
                                        <button type="submit" class="btn btn-primary"><i class="ri-save-3-line me-1"></i>Save post settings</button>
                                    </div>
                                </form>
                            </div>
                            @endif

                            @if(! empty($canSettingsPopup))
                            <div class="platform-settings {{ $dp === 'popupSettings' ? 'active' : '' }}" id="popupSettings-settings">
                                @include('admin.settings.popup-tab', ['popupSettings' => $popupSettings ?? []])
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(! empty($canSettingsMail))
    <div class="modal fade" id="mailTestModal" tabindex="-1" aria-labelledby="mailTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mailTestModalLabel"><i class="ri-mail-send-line me-2"></i>Send test mail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="mailTestForm" method="post" action="#">
                    <div class="modal-body">
                        <div id="mailTestAlert" class="mb-3" role="status"></div>
                        <label class="form-label" for="mail_test_email">Recipient email</label>
                        <input type="email" class="form-control" id="mail_test_email" name="email" required placeholder="you@example.com" autocomplete="email">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="mailTestSubmitBtn"><i class="ri-send-plane-line me-1"></i> Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if(! empty($canSettingsSms))
    <div class="modal fade" id="msg91TemplatesModal" tabindex="-1" aria-labelledby="msg91TemplatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="msg91TemplatesModalLabel"><i class="ri-settings-3-line me-2"></i>MSG91 template IDs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Keys must be lowercase with underscores. Saved with the rest of site settings.</p>
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-sm btn-primary" id="msg91TemplatesAddRow"><i class="ri-add-line me-1"></i> Add row</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" id="msg91TemplatesTable">
                            <thead class="table-light"><tr><th>Key</th><th>Template ID</th><th class="text-center" style="width:56px">—</th></tr></thead>
                            <tbody id="msg91TemplatesTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="msg91TemplatesSaveBtn"><i class="ri-save-3-line me-1"></i> Save templates</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(! empty($canSettingsSiteInfo))
    @include('admin.components.media-library-modal', [
        'modalId' => 'mediaLibraryModal',
        'modalTitle' => 'Select image',
        'setButtonText' => 'Use this image',
        'mediaTypes' => $mediaTypes,
        'dates' => $dates,
    ])
    @endif
@endsection

@section('script')
    @php
        $settingsPageConfigForJs = [
            'indexUrl' => route('administrator.settings.edit'),
            'storageKey' => 'guides_admin_settings_tab',
            'defaultTab' => $settingsDefaultPlatform ?? 'sitesinfo',
            'tabs' => [
                'sitesinfo' => ! empty($canSettingsSiteInfo),
                'smsConfig' => ! empty($canSettingsSms),
                'mailConfig' => ! empty($canSettingsMail),
                'themeMode' => ! empty($canSettingsThemeMode),
                'postSettings' => ! empty($canSettingsPost),
                'popupSettings' => ! empty($canSettingsPopup),
            ],
        ];
    @endphp
    <script>
        window.settingsData = { msg91Templates: @json($msg91Templates) };
        window.settingsPageConfig = @json($settingsPageConfigForJs);
    </script>
    @if(! empty($canSettingsSiteInfo))
    @include('admin.partials.media-library-script')
    @endif
@endsection

@section('script-bottom')
    <script>
    (function () {
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const storeUrl = @json(route('administrator.settings.store'));
        const msg91TemplatesUrl = @json(route('administrator.settings.msg91'));
        const mailEnvUrl = @json(route('administrator.settings.mail'));
        const mailTestUrl = @json(route('administrator.settings.mail-test'));
        const pageCfg = window.settingsPageConfig || { indexUrl: '', storageKey: 'guides_admin_settings_tab', defaultTab: 'sitesinfo', tabs: {} };

        function tabAllowed(platform) {
            return !!(pageCfg.tabs && pageCfg.tabs[platform]);
        }

        function persistTab(platform) {
            if (!tabAllowed(platform)) return;
            try { localStorage.setItem(pageCfg.storageKey, platform); } catch (e) { /* ignore */ }
        }

        function activateSettingsTab(platform) {
            if (!tabAllowed(platform)) return;
            document.querySelectorAll('.settings-page .list-group-item[data-platform]').forEach(function (l) {
                var on = l.getAttribute('data-platform') === platform;
                l.classList.toggle('active', on);
                l.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            document.querySelectorAll('.settings-page .platform-settings').forEach(function (p) {
                p.classList.toggle('active', p.id === platform + '-settings');
            });
            persistTab(platform);
        }

        function pickInitialTab() {
            var params = new URLSearchParams(window.location.search);
            var urlTab = params.get('tab');
            if (urlTab && tabAllowed(urlTab)) return urlTab;
            try {
                var s = localStorage.getItem(pageCfg.storageKey);
                if (s && tabAllowed(s)) return s;
            } catch (e) { /* ignore */ }
            return pageCfg.defaultTab || 'sitesinfo';
        }

        function showInline(el, msg, isError) {
            if (!el) return;
            el.innerHTML = msg
                ? '<div class="alert alert-' + (isError ? 'danger' : 'success') + ' py-1 px-2 mb-0 small">' + msg + '</div>'
                : '';
        }

        function showToast(msg) {
            if (typeof window.showSuccessToast === 'function') { window.showSuccessToast(msg); return; }
            alert(msg);
        }

        function showSettingsErrorSwal(title, text) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: title || 'Error', text: text || 'Something went wrong.' });
            } else {
                alert(text || title || 'Error');
            }
        }

        function notifySavedThenReload(tab, title, message) {
            var msg = message || 'Your changes were saved.';
            var go = function () {
                persistTab(tab);
                var u = new URL(pageCfg.indexUrl, window.location.href);
                u.searchParams.set('tab', tab);
                window.location.href = u.toString();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: title || 'Saved',
                    text: msg,
                    confirmButtonText: 'OK',
                }).then(go);
            } else {
                go();
            }
        }

        activateSettingsTab(pickInitialTab());

        document.querySelectorAll('.settings-page .list-group-item[data-platform]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var platform = this.getAttribute('data-platform');
                activateSettingsTab(platform);
            });
        });

        function mountMediaInContainer(container) {
            const selectBtn = container.querySelector('.media-select-btn');
            const removeBtn = container.querySelector('.remove-image-btn');
            const area = container.querySelector('.media-select-area');
            const img = container.querySelector('.media-preview');
            const placeholder = container.querySelector('.media-placeholder');
            const idIn = container.querySelector('.media-input');
            const urlIn = container.querySelector('.media-url-input');

            function setPreview(url) {
                if (url) {
                    if (img) { img.src = url; img.style.display = 'block'; }
                    if (placeholder) placeholder.style.display = 'none';
                    if (removeBtn) removeBtn.classList.remove('d-none');
                } else {
                    if (img) { img.src = ''; img.style.display = 'none'; }
                    if (placeholder) placeholder.style.display = 'block';
                    if (removeBtn) removeBtn.classList.add('d-none');
                }
            }
            if (selectBtn) {
                selectBtn.addEventListener('click', function (e) { e.preventDefault(); if (typeof openMediaLibrary !== 'function') return;
                    openMediaLibrary('mediaLibraryModal', { title: 'Select image', setButtonText: 'Use this image', forcedType: 'image', apiMode: 'common',
                    onImageSelected: function (item) {
                        if (!item) return;
                        if (idIn) idIn.value = String(item.id || '');
                        if (urlIn) urlIn.value = item.url || '';
                        setPreview(item.url);
                        const el = document.getElementById('mediaLibraryModal');
                        if (el && window.bootstrap) { (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide(); }
                    } });
                });
            }
            if (area) { area.addEventListener('click', function () { selectBtn && selectBtn.click(); }); }
            if (removeBtn) {
                removeBtn.addEventListener('click', function (e) { e.preventDefault();
                    if (idIn) idIn.value = '';
                    if (urlIn) urlIn.value = '';
                    setPreview(null);
                });
            }
        }
        document.querySelectorAll('.settings-page .js-settings-media').forEach(mountMediaInContainer);

        function submitFormData(form, alertEl) {
            return fetch(storeUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); });
        }
        function expandShortHex(v) {
            if (!v || v.length !== 4 || v[0] !== '#') {
                return v;
            }
            return '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
        }
        function bindMainNavBgPair(textEl, pickerEl) {
            if (!textEl || !pickerEl) return;
            pickerEl.addEventListener('input', function () {
                textEl.value = this.value;
            });
            textEl.addEventListener('input', function () {
                var t = this.value.trim();
                if (/^#[0-9A-Fa-f]{6}$/i.test(t)) {
                    pickerEl.value = t;
                } else if (/^#[0-9A-Fa-f]{3}$/i.test(t)) {
                    pickerEl.value = expandShortHex(t);
                }
            });
        }
        bindMainNavBgPair(document.getElementById('main-nav-bg'), document.getElementById('main-nav-bg-picker'));
        bindMainNavBgPair(document.getElementById('theme-mode-main-nav-bg'), document.getElementById('theme-mode-main-nav-bg-picker'));

        document.querySelectorAll('.theme-mode-hex-picker').forEach(function (picker) {
            var tid = picker.getAttribute('data-target-id');
            var target = tid ? document.getElementById(tid) : null;
            if (!target) return;
            picker.addEventListener('input', function () {
                target.value = this.value;
            });
            target.addEventListener('input', function () {
                var t = this.value.trim();
                if (/^#[0-9A-Fa-f]{6}$/i.test(t)) {
                    picker.value = t;
                } else if (/^#[0-9A-Fa-f]{3}$/i.test(t)) {
                    picker.value = expandShortHex(t);
                }
            });
        });

        const siteInfoForm = document.getElementById('siteInfoSettingForm');
        if (siteInfoForm) {
            siteInfoForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const al = document.getElementById('siteInfoAlert');
                showInline(al, '');
                submitFormData(siteInfoForm, al).then(function (r) {
                    if (r.ok && r.d.success) {
                        notifySavedThenReload('sitesinfo', 'Site settings saved', r.d.message);
                    } else {
                        var em = (r.d && r.d.message) ? r.d.message : 'Save failed.';
                        showInline(al, em, true);
                        showSettingsErrorSwal('Could not save', em);
                    }
                }).catch(function () {
                    showInline(al, 'Request failed.', true);
                    showSettingsErrorSwal('Request failed', 'Please try again.');
                });
            });
        }
        const themeModeForm = document.getElementById('themeModeSettingForm');
        if (themeModeForm) {
            themeModeForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const al = document.getElementById('themeModeAlert');
                showInline(al, '');
                submitFormData(themeModeForm, al).then(function (r) {
                    if (r.ok && r.d.success) {
                        notifySavedThenReload('themeMode', 'Theme settings saved', r.d.message);
                    } else {
                        var em = (r.d && r.d.message) ? r.d.message : 'Save failed.';
                        showInline(al, em, true);
                        showSettingsErrorSwal('Could not save', em);
                    }
                }).catch(function () {
                    showInline(al, 'Request failed.', true);
                    showSettingsErrorSwal('Request failed', 'Please try again.');
                });
            });
        }
        const postSettingsForm = document.getElementById('postSettingsSettingForm');
        if (postSettingsForm) {
            postSettingsForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const al = document.getElementById('postSettingsAlert');
                showInline(al, '');
                submitFormData(postSettingsForm, al).then(function (r) {
                    if (r.ok && r.d.success) {
                        notifySavedThenReload('postSettings', 'Post settings saved', r.d.message);
                    } else {
                        var em = (r.d && r.d.message) ? r.d.message : 'Save failed.';
                        showInline(al, em, true);
                        showSettingsErrorSwal('Could not save', em);
                    }
                }).catch(function () {
                    showInline(al, 'Request failed.', true);
                    showSettingsErrorSwal('Request failed', 'Please try again.');
                });
            });
        }
        const popupSettingsForm = document.getElementById('popupSettingsForm');
        if (popupSettingsForm) {
            popupSettingsForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const al = document.getElementById('popupSettingsAlert');
                showInline(al, '');
                submitFormData(popupSettingsForm, al).then(function (r) {
                    if (r.ok && r.d.success) {
                        notifySavedThenReload('popupSettings', 'Popup settings saved', r.d.message);
                    } else {
                        var em = (r.d && r.d.message) ? r.d.message : 'Save failed.';
                        showInline(al, em, true);
                        showSettingsErrorSwal('Could not save', em);
                    }
                }).catch(function () {
                    showInline(al, 'Request failed.', true);
                    showSettingsErrorSwal('Request failed', 'Please try again.');
                });
            });
        }
        const smsForm = document.getElementById('smsMsg91SettingForm');
        if (smsForm) {
            smsForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const al = document.getElementById('smsFormAlert');
                showInline(al, '');
                submitFormData(smsForm, al).then(function (r) {
                    if (r.ok && r.d.success) {
                        notifySavedThenReload('smsConfig', 'SMS configuration saved', r.d.message);
                    } else {
                        var em = (r.d && r.d.message) ? r.d.message : 'Save failed.';
                        showInline(al, em, true);
                        showSettingsErrorSwal('Could not save', em);
                    }
                }).catch(function () {
                    showInline(al, 'Request failed.', true);
                    showSettingsErrorSwal('Request failed', 'Please try again.');
                });
            });
        }

        const mailEnvForm = document.getElementById('mailEnvSettingForm');
        if (mailEnvForm) {
            mailEnvForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const al = document.getElementById('mailEnvAlert');
                showInline(al, '');
                fetch(mailEnvUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(mailEnvForm)
                }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (res.ok && res.d.success) {
                            notifySavedThenReload('mailConfig', 'Mail configuration saved', res.d.message);
                        } else {
                            var msg = (res.d && res.d.message) ? res.d.message : 'Save failed.';
                            if (res.d && res.d.errors) {
                                var parts = [];
                                Object.keys(res.d.errors).forEach(function (k) {
                                    var arr = res.d.errors[k];
                                    if (Array.isArray(arr)) { parts = parts.concat(arr); }
                                });
                                if (parts.length) { msg = parts.join(' '); }
                            }
                            showInline(al, msg, true);
                            showSettingsErrorSwal('Could not save', msg);
                        }
                    })
                    .catch(function () {
                        showInline(al, 'Request failed.', true);
                        showSettingsErrorSwal('Request failed', 'Please try again.');
                    });
            });
        }

        const mailTestForm = document.getElementById('mailTestForm');
        const mailTestModalEl = document.getElementById('mailTestModal');
        if (mailTestModalEl) {
            mailTestModalEl.addEventListener('show.bs.modal', function () {
                showInline(document.getElementById('mailTestAlert'), '', false);
                var em = document.getElementById('mail_test_email');
                if (em) { em.focus(); }
            });
        }
        if (mailTestForm) {
            mailTestForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const al = document.getElementById('mailTestAlert');
                const btn = document.getElementById('mailTestSubmitBtn');
                const emailIn = document.getElementById('mail_test_email');
                showInline(al, '');
                if (!emailIn || !emailIn.checkValidity()) {
                    if (emailIn) { emailIn.reportValidity(); }
                    return;
                }
                var fd = new FormData();
                fd.append('email', emailIn.value.trim());
                if (btn) { btn.disabled = true; }
                fetch(mailTestUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (res.ok && res.d.success) {
                            showInline(al, res.d.message || 'Sent.', false);
                            var sentMsg = res.d.message || 'Test email sent.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Mail sent', text: sentMsg, confirmButtonText: 'OK' });
                            } else {
                                showToast(sentMsg);
                            }
                            var el = document.getElementById('mailTestModal');
                            if (el && window.bootstrap) {
                                (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
                            }
                        } else {
                            var msg = (res.d && res.d.message) ? res.d.message : 'Send failed.';
                            if (res.d && res.d.errors && res.d.errors.email) {
                                var arr = res.d.errors.email;
                                msg = Array.isArray(arr) ? arr[0] : msg;
                            }
                            showInline(al, msg, true);
                            showSettingsErrorSwal('Could not send', msg);
                        }
                    })
                    .catch(function () { showInline(al, 'Request failed.', true); })
                    .finally(function () { if (btn) { btn.disabled = false; } });
            });
        }

        let msg91TemplatesState = (window.settingsData && window.settingsData.msg91Templates) ? Object.assign({}, window.settingsData.msg91Templates) : {};

        function toRows(obj) {
            const rows = [];
            if (obj && typeof obj === 'object') { Object.keys(obj).forEach(function (k) { rows.push({ key: k, id: String(obj[k] != null ? obj[k] : '') }); }); }
            if (!rows.length) { rows.push({ key: '', id: '' }); }
            return rows;
        }
        function rowHtml(key, id) {
            const tr = document.createElement('tr');
            tr.className = 'msg91-template-row';
            const td1 = document.createElement('td');
            const in1 = document.createElement('input');
            in1.type = 'text';
            in1.className = 'form-control form-control-sm msg91-tpl-key';
            in1.value = key || '';
            in1.setAttribute('placeholder', 'e.g. login_otp');
            td1.appendChild(in1);
            const td2 = document.createElement('td');
            const in2 = document.createElement('input');
            in2.type = 'text';
            in2.className = 'form-control form-control-sm msg91-tpl-id';
            in2.value = id || '';
            in2.setAttribute('placeholder', 'Template ID');
            td2.appendChild(in2);
            const td3 = document.createElement('td');
            td3.className = 'text-center';
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-outline-danger template-row-remove';
            b.title = 'Remove';
            b.setAttribute('aria-label', 'Remove');
            b.innerHTML = '<i class="ri-delete-bin-line"></i>';
            td3.appendChild(b);
            tr.appendChild(td1);
            tr.appendChild(td2);
            tr.appendChild(td3);
            return tr;
        }
        function renderMsg91Table() {
            const tbody = document.getElementById('msg91TemplatesTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            toRows(msg91TemplatesState).forEach(function (r) { tbody.appendChild(rowHtml(r.key, r.id)); });
        }
        const modal = document.getElementById('msg91TemplatesModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function () { renderMsg91Table(); });
        }
        document.getElementById('msg91TemplatesAddRow')?.addEventListener('click', function () {
            const tbody = document.getElementById('msg91TemplatesTableBody');
            if (!tbody) return;
            tbody.appendChild(rowHtml('', ''));
        });
        document.getElementById('msg91TemplatesTableBody')?.addEventListener('click', function (e) {
            const btn = e.target.closest && e.target.closest('.template-row-remove');
            if (!btn) return;
            const tr = btn.closest('tr');
            const rows = this.querySelectorAll('tr');
            if (rows.length <= 1) { tr.querySelectorAll('input').forEach(function (i) { i.value = ''; }); return; }
            tr.remove();
        });
        document.getElementById('msg91TemplatesSaveBtn')?.addEventListener('click', function () {
            const rows = [];
            document.querySelectorAll('#msg91TemplatesTableBody tr').forEach(function (tr) {
                rows.push({ key: tr.querySelector('.msg91-tpl-key')?.value.trim() || '', id: tr.querySelector('.msg91-tpl-id')?.value.trim() || '' });
            });
            const btn = this; btn.disabled = true;
            fetch(msg91TemplatesUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ templates: rows })
            }).then(function (r) { return r.json().then(function (d) { return { status: r.status, d: d }; }); })
            .then(function (o) {
                if (o.d.success && o.d.templates) { msg91TemplatesState = o.d.templates; if (window.settingsData) { window.settingsData.msg91Templates = o.d.templates; } }
                if (o.d.success) {
                    const el = document.getElementById('msg91TemplatesModal');
                    if (el && window.bootstrap) { (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide(); }
                    notifySavedThenReload('smsConfig', 'MSG91 templates saved', o.d.message);
                } else {
                    showSettingsErrorSwal('Could not save', o.d.message || 'Save failed.');
                }
            })
            .catch(function () { showSettingsErrorSwal('Request failed', 'Please try again.'); })
            .finally(function () { btn.disabled = false; });
        });
    })();
    </script>
@endsection
