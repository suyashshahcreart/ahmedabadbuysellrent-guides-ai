@extends('admin.layouts.vertical', ['title' => 'Settings'])

@section('content')
<h3 class="mb-3">Settings</h3>
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-site" type="button">Site info</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-theme" type="button">Theme mode</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-post" type="button">Post</button></li>
</ul>
<form method="post" action="{{ route('administrator.settings.update') }}">
@csrf
<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-site">
        <div class="card panel-card border-primary border-top" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Site info'])
            <div class="card-body">
                <div class="row g-3">
                    @foreach([
                        'site_name' => 'Site name',
                        'site_short_name' => 'Short name',
                        'site_url' => 'Site URL',
                        'logo_url' => 'Logo URL',
                        'logo_lg_url' => 'Large logo URL',
                        'logo_sm_url' => 'Compact logo URL',
                        'favicon_url' => 'Favicon URL',
                        'cta_url' => 'CTA URL',
                        'cta_label' => 'CTA label',
                        'default_seo_title' => 'Default SEO title',
                        'default_meta_description' => 'Default meta description',
                        'default_og_image' => 'Default OG image URL',
                        'copyright' => 'Copyright',
                        'contact_email' => 'Contact email',
                    ] as $k => $label)
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        <input class="form-control" name="{{ $k }}" value="{{ $settings[$k] ?? '' }}">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="tab-theme">
        <div class="card panel-card border-primary border-top" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Theme mode'])
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Sidebar background</label>
                    <input type="color" class="form-control form-control-color" name="main_nav_bg" value="{{ $settings['main_nav_bg'] }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Logo large height</label>
                    <input class="form-control" name="logo_lg_height" value="{{ $settings['logo_lg_height'] ?? '28px' }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Logo small height</label>
                    <input class="form-control" name="logo_sm_height" value="{{ $settings['logo_sm_height'] ?? '22px' }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Optional CSS variable overrides</label>
                    <textarea class="form-control" name="theme_mode_css" rows="8" placeholder=".wrapper { --bs-primary: #000040; }">{{ $settings['theme_mode_css'] }}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="tab-post">
        <div class="card panel-card border-primary border-top" data-panel-card>
            @include('admin.master.partials.panel-card-header-form', ['cardTitle' => 'Post'])
            <div class="card-body">
                <label class="form-label">Autosave interval (seconds)</label>
                <input type="number" class="form-control" name="autosave_interval" value="{{ $settings['autosave_interval'] }}" min="0">
                <div class="form-text">Stored for a future autosave client. Saving is still manual until autosave ships.</div>
            </div>
        </div>
    </div>
</div>
<p class="mt-3"><button class="btn btn-primary" type="submit">Save settings</button></p>
</form>
@endsection
