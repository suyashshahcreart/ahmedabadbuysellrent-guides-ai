<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Setting;
use App\Services\MailEnvFileService;
use App\Services\Popup\PopupSettingsService;
use App\Support\MediaLibraryUi;
use App\Support\SiteBranding;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        try {
            app(MailEnvFileService::class)->applyToConfig();
        } catch (\Throwable $e) {
            // Settings table may be missing during first install.
        }

        View::composer(['admin.*'], function ($view) {
            try {
                $view->with('site', SiteBranding::forView());
            } catch (\Throwable $e) {
                $view->with('site', [
                    'name' => config('app.name', 'Ahmedabad Guides'),
                    'short_name' => 'Guides',
                    'favicon' => '/favicon.svg',
                    'logo_lg_height' => '30px',
                    'logo_sm_height' => '28px',
                    'main_nav_bg' => '#000040',
                    'theme_mode_css' => '',
                    'show_name' => true,
                    'sidebar' => [
                        'dark_sm' => '/favicon.svg',
                        'dark_lg' => '/favicon.svg',
                        'light_sm' => '/favicon.svg',
                        'light_lg' => '/favicon.svg',
                    ],
                    'auth' => [
                        'dark' => '/favicon.svg',
                        'light' => '/favicon.svg',
                        'height_px' => 32,
                    ],
                ]);
            }
            $view->with('mediaTypes', MediaLibraryUi::typeGroups());
            $view->with('mediaDates', MediaLibraryUi::dateOptions());
        });

        View::composer(['public.*', 'errors.*'], function ($view) {
            try {
                $branding = SiteBranding::forView();
                $view->with('siteName', $branding['name'] ?? Setting::get('site_name', config('app.name')));
                $view->with('siteShort', $branding['short_name'] ?? Setting::get('site_short_name', 'Ahmedabad Guides'));
                $view->with('siteFavicon', $branding['favicon'] ?? asset('favicon.svg'));
                $view->with('ctaUrl', Setting::get('cta_url'));
                $view->with('ctaLabel', Setting::get('cta_label', 'Explore properties'));
                $view->with('headerMenu', Cache::remember('menu_header', 300, fn () => Menu::with(['items.children'])->where('location', 'header')->first()));
                $view->with('footerMenu', Cache::remember('menu_footer', 300, fn () => Menu::with(['items.children'])->where('location', 'footer')->first()));
                $view->with('navCategories', Cache::remember('nav_categories', 300, fn () => Category::query()->where('status', 'published')->orderBy('sort_order')->get()));
                $view->with('sitePopup', Cache::remember('guides.site_popup', 60, fn () => app(PopupSettingsService::class)->all()));
            } catch (\Throwable $e) {
                $view->with('siteName', config('app.name'));
                $view->with('siteShort', 'Ahmedabad Guides');
                $view->with('siteFavicon', asset('favicon.svg'));
                $view->with('ctaUrl', null);
                $view->with('ctaLabel', 'Explore guides');
                $view->with('headerMenu', null);
                $view->with('footerMenu', null);
                $view->with('navCategories', collect());
                $view->with('sitePopup', []);
            }
        });
    }
}
