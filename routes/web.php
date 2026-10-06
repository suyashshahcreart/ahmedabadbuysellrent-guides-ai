<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\Ai\AutomationController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuthorController as AdminAuthorController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CreditManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InsightController;
use App\Http\Controllers\Admin\LocalityController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoDashboardController;
use App\Http\Controllers\Admin\SeoMetaController;
use App\Http\Controllers\Admin\SeoSchemaController;
use App\Http\Controllers\Admin\SeoTemplateController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SitemapSettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Web\AreaController;
use App\Http\Controllers\Web\AuthorController as WebAuthorController;
use App\Http\Controllers\Web\GuideController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\HubController;
use App\Http\Controllers\Web\InsightController as WebInsightController;
use App\Http\Controllers\Web\PreviewController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\TagController as WebTagController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-{type}.xml', [SitemapController::class, 'show']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

Route::prefix('administrator')->name('administrator.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('profile', [DashboardController::class, 'profile']);
        Route::post('profile', [DashboardController::class, 'updateProfile']);

        Route::get('articles/tags/suggest', [ArticleController::class, 'suggestTags'])->name('articles.tags.suggest');
        Route::resource('articles', ArticleController::class)->except(['show']);
        Route::post('articles/bulk', [ArticleController::class, 'bulk'])->name('articles.bulk');
        Route::post('articles/{article}/duplicate', [ArticleController::class, 'duplicate'])->name('articles.duplicate');
        Route::put('articles/{article}/quick', [ArticleController::class, 'quickUpdate'])->name('articles.quick');
        Route::post('articles/{article}/preview', [ArticleController::class, 'preview']);
        Route::post('articles/{id}/restore', [ArticleController::class, 'restore'])->name('articles.restore');
        Route::post('articles/{article}/seo/schemas', [ArticleController::class, 'storeSeoSchema'])->name('articles.seo.schemas.store');
        Route::put('articles/{article}/seo/schemas/{seoSchema}', [ArticleController::class, 'updateSeoSchema'])->name('articles.seo.schemas.update');
        Route::delete('articles/{article}/seo/schemas/{seoSchema}', [ArticleController::class, 'deleteSeoSchema'])->name('articles.seo.schemas.delete');
        Route::post('articles/{article}/seo/schemas/generate', [ArticleController::class, 'generateSeoSchema'])->name('articles.seo.schemas.generate');
        Route::post('articles/{article}/seo/schemas/generate-all', [ArticleController::class, 'generateAllSeoSchemas'])->name('articles.seo.schemas.generate-all');

        Route::resource('pages', AdminPageController::class)->except(['show']);
        Route::post('pages/{page}/preview', [AdminPageController::class, 'preview']);

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
        Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::resource('tags', TagController::class)->except(['show']);
        Route::resource('authors', AdminAuthorController::class)->except(['show']);
        Route::resource('localities', LocalityController::class)->except(['show']);
        Route::resource('insights', InsightController::class)->except(['show']);

        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::get('media/json', [MediaController::class, 'json'])->name('media.json');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::get('media/next/{media_asset}', [MediaController::class, 'next'])->name('media.next');
        Route::get('media/previous/{media_asset}', [MediaController::class, 'previous'])->name('media.previous');
        Route::get('media/{media_asset}', [MediaController::class, 'show'])->name('media.show');
        Route::put('media/{media_asset}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media_asset}', [MediaController::class, 'destroy'])->name('media.destroy');

        Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
        Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
        Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
        Route::patch('faqs/{faq}/toggle-status', [FaqController::class, 'toggleStatus'])->name('faqs.toggle-status');
        Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

        Route::get('seo', [SeoDashboardController::class, 'audit'])->name('seo.audit');
        Route::get('seo/meta', [SeoMetaController::class, 'index'])->name('seo.meta.index');
        Route::get('seo/meta/{seoMeta}/edit', [SeoMetaController::class, 'edit'])->name('seo.meta.edit');
        Route::put('seo/meta/{seoMeta}', [SeoMetaController::class, 'update'])->name('seo.meta.update');
        Route::resource('seo/templates', SeoTemplateController::class)->except(['show'])->parameters(['templates' => 'template'])->names('seo.templates');
        Route::get('seo/schemas', [SeoSchemaController::class, 'index'])->name('seo.schemas.index');
        Route::get('seo/sitemap', [SitemapSettingsController::class, 'index'])->name('seo.sitemap.index');
        Route::post('seo/sitemap', [SitemapSettingsController::class, 'update'])->name('seo.sitemap.update');
        Route::post('seo/sitemap/rebuild', [SitemapSettingsController::class, 'rebuild'])->name('seo.sitemap.rebuild');
        Route::post('seo/sitemap/clear', [SitemapSettingsController::class, 'clear'])->name('seo.sitemap.clear');
        Route::get('seo/redirects', fn () => redirect()->route('administrator.redirects.index', [], 301));
        Route::get('redirects', [RedirectController::class, 'index'])->name('redirects.index');
        Route::get('redirects/create', [RedirectController::class, 'create'])->name('redirects.create');
        Route::post('redirects', [RedirectController::class, 'store'])->name('redirects.store');
        Route::delete('redirects/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy');

        Route::get('menus', [MenuController::class, 'index']);
        Route::post('menus/{menu}/items', [MenuController::class, 'storeItem']);
        Route::delete('menu-items/{item}', [MenuController::class, 'destroyItem']);

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
        Route::post('settings/update', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/mail', [SettingController::class, 'saveMailEnv'])->name('settings.mail');
        Route::post('settings/mail-test', [SettingController::class, 'sendTestMail'])->name('settings.mail-test');
        Route::post('settings/msg91', [SettingController::class, 'saveMsg91Templates'])->name('settings.msg91');

        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);

        Route::get('ai/credits', [CreditManagementController::class, 'index'])->name('ai.credits.index');
        Route::get('ai/credits/users', [CreditManagementController::class, 'users'])->name('ai.credits.users');
        Route::post('ai/credits', [CreditManagementController::class, 'update'])->name('ai.credits.update');
        Route::get('ai/credits/{user}/history', [CreditManagementController::class, 'history'])->name('ai.credits.history');
        Route::get('ai/credits/{user}/usage', [CreditManagementController::class, 'usage'])->name('ai.credits.usage');

        Route::get('ai/automation', [AutomationController::class, 'index'])->name('ai.automation.index');
        Route::post('ai/automation', [AutomationController::class, 'update'])->name('ai.automation.update');
        Route::post('ai/automation/start', [AutomationController::class, 'start'])->name('ai.automation.start');
        Route::post('ai/automation/pause', [AutomationController::class, 'pause'])->name('ai.automation.pause');
        Route::post('ai/automation/generate-once', [AutomationController::class, 'generateOnce'])->name('ai.automation.generate-once');
        Route::get('ai/automation/reports', [AutomationController::class, 'reports'])->name('ai.automation.reports');
        Route::get('ai/automation/runs/{run}', [AutomationController::class, 'showRun'])->name('ai.automation.runs.show');
        Route::put('ai/automation/runs/{run}/target', [AutomationController::class, 'updateRunTarget'])->name('ai.automation.runs.target');
        Route::post('ai/automation/runs/{run}/start', [AutomationController::class, 'startRun'])->name('ai.automation.runs.start');
        Route::post('ai/automation/runs/{run}/stop', [AutomationController::class, 'stopRun'])->name('ai.automation.runs.stop');
        Route::get('ai/automation/templates', [AutomationController::class, 'templates'])->name('ai.automation.templates');
        Route::put('ai/automation/templates/{template}', [AutomationController::class, 'updateTemplate'])->name('ai.automation.templates.update');
        Route::get('ai/automation/geo', [AutomationController::class, 'geo'])->name('ai.automation.geo');
        Route::get('ai/automation/editors', [AutomationController::class, 'editors'])->name('ai.automation.editors');
        Route::post('ai/automation/ensure-editors', [AutomationController::class, 'ensureEditors'])->name('ai.automation.ensure-editors');
        Route::post('ai/automation/seed-pools', [AutomationController::class, 'seedPools'])->name('ai.automation.seed-pools');

        Route::get('revisions/{revision}', [RevisionController::class, 'show']);
        Route::post('revisions/{revision}/restore', [RevisionController::class, 'restore']);
        Route::get('activity', [ActivityController::class, 'index']);
    });
});

Route::get('/preview/article/{article}', [PreviewController::class, 'article'])->name('preview.article');
Route::get('/preview/page/{page}', [PreviewController::class, 'page'])->name('preview.page');

Route::get('/', HomeController::class);
Route::get('/guides/', [GuideController::class, 'index']);
Route::get('/guides/{slug}/', [GuideController::class, 'show']);
Route::get('/compare/{slug}/', [GuideController::class, 'compare']);
Route::get('/areas/{slug}/', [AreaController::class, 'show']);
Route::get('/search/', SearchController::class);
Route::get('/tags/{slug}/', [WebTagController::class, 'show']);
Route::get('/authors/{slug}/', [WebAuthorController::class, 'show']);
Route::get('/market/{slug}/', [WebInsightController::class, 'show'])->where('slug', '^(?!$).+');
Route::get('/{slug}/', HubController::class)->where('slug', '^(?!administrator|storage|preview).+');
