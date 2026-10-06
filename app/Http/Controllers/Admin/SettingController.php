<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Admin\PostEditorSettingsService;
use App\Services\MailEnvFileService;
use App\Services\Popup\PopupSettingsService;
use App\Support\MediaLibraryUi;
use App\Support\ThemeModeCssOverrides;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const SMS_SETTINGS_OPTION_KEYS = [
        'active_sms_gateway',
        'sms_gateway_msg91_status',
        'msg91_sender_id',
        'msg91_timeout',
        'msg91_auth_key',
    ];

    public function __construct()
    {
        $this->middleware('permission:settings.view|settings.edit|settings.manage|settings.site|settings.sms|settings.mail|settings.theme|settings.post|settings.popup')->only(['edit', 'index']);
        $this->middleware('permission:settings.edit|settings.manage|settings.site|settings.sms|settings.mail|settings.theme|settings.post|settings.popup')->only(['store', 'update', 'saveMailEnv', 'sendTestMail', 'saveMsg91Templates']);
    }

    public function edit(Request $request): View
    {
        return $this->index($request);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $settings = Setting::query()->pluck('value', 'key')->toArray();
        if (empty($settings['websitetitle']) && ! empty($settings['site_name'])) {
            $settings['websitetitle'] = $settings['site_name'];
        }
        if (empty($settings['favicon_image_url']) && ! empty($settings['favicon_url'])) {
            $settings['favicon_image_url'] = $settings['favicon_url'];
        }
        if (empty($settings['sidebar_logo_dark_lg_url']) && ! empty($settings['logo_lg_url'])) {
            $settings['sidebar_logo_dark_lg_url'] = $settings['logo_lg_url'];
        }
        if (empty($settings['sidebar_logo_dark_sm_url']) && ! empty($settings['logo_sm_url'])) {
            $settings['sidebar_logo_dark_sm_url'] = $settings['logo_sm_url'];
        }

        $rawTemplates = Setting::get('msg91_templates', '{}');
        $msg91Templates = is_array($rawTemplates) ? $rawTemplates : (json_decode((string) $rawTemplates, true) ?: []);

        $canAll = $user->can('settings.edit') || $user->can('settings.manage');
        $canSettingsSiteInfo = $canAll || $user->can('settings.site');
        $canSettingsSms = $canAll || $user->can('settings.sms');
        $canSettingsMail = $canAll || $user->can('settings.mail');
        $canSettingsThemeMode = $canAll || $user->can('settings.theme');
        $canSettingsPost = $canAll || $user->can('settings.post');
        $canSettingsPopup = $canAll || $user->can('settings.popup');

        return view('admin.settings.index', [
            'settings' => $settings,
            'msg91Templates' => $msg91Templates,
            'mailEnv' => app(MailEnvFileService::class)->read(),
            'mediaTypes' => MediaLibraryUi::typeGroups(),
            'dates' => MediaLibraryUi::dateOptions(),
            'canSettingsSiteInfo' => $canSettingsSiteInfo,
            'canSettingsSms' => $canSettingsSms,
            'canSettingsMail' => $canSettingsMail,
            'canSettingsThemeMode' => $canSettingsThemeMode,
            'canSettingsPost' => $canSettingsPost,
            'canSettingsPopup' => $canSettingsPopup,
            'popupSettings' => app(PopupSettingsService::class)->all(),
            'postEditorSettings' => app(PostEditorSettingsService::class)->allSeconds(),
            'themeModeFieldGroups' => $this->themeModeFieldGroups(),
            'settingsDefaultPlatform' => $this->resolveActiveSettingsTab($request, $canSettingsSiteInfo, $canSettingsSms, $canSettingsMail, $canSettingsThemeMode, $canSettingsPost, $canSettingsPopup),
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        return $this->store($request);
    }

    private function resolveActiveSettingsTab(Request $request, bool $site, bool $sms, bool $mail, bool $theme, bool $post, bool $popup = false): string
    {
        $allowed = [];
        if ($site) {
            $allowed['sitesinfo'] = true;
        }
        if ($sms) {
            $allowed['smsConfig'] = true;
        }
        if ($mail) {
            $allowed['mailConfig'] = true;
        }
        if ($theme) {
            $allowed['themeMode'] = true;
        }
        if ($post) {
            $allowed['postSettings'] = true;
        }
        if ($popup) {
            $allowed['popupSettings'] = true;
        }

        $requested = $request->query('tab');
        if (is_string($requested) && $requested !== '' && isset($allowed[$requested])) {
            return $requested;
        }

        foreach (array_keys($allowed) as $tab) {
            return $tab;
        }

        return 'sitesinfo';
    }

    /**
     * @return list<array{selector: string, title: string, fields: array<string, array{0: string, 1: string, 2: string}>}>
     */
    private function themeModeFieldGroups(): array
    {
        $meta = config('theme-mode-defaults.meta', []);
        $labels = config('theme-mode-defaults.selector_labels', []);

        $bySelector = [];
        foreach ($meta as $optionKey => $tuple) {
            $sel = $tuple[0];
            if (! isset($bySelector[$sel])) {
                $bySelector[$sel] = [];
            }
            $bySelector[$sel][$optionKey] = $tuple;
        }

        $groups = [];
        foreach ($labels as $selector => $title) {
            if (! empty($bySelector[$selector])) {
                $groups[] = [
                    'selector' => $selector,
                    'title' => $title,
                    'fields' => $bySelector[$selector],
                ];
                unset($bySelector[$selector]);
            }
        }
        foreach ($bySelector as $selector => $fields) {
            $groups[] = [
                'selector' => $selector,
                'title' => $selector,
                'fields' => $fields,
            ];
        }

        return $groups;
    }

    private static function sanitizeMainNavBgOption(string $raw): string
    {
        $t = trim($raw);
        if ($t === '') {
            return '';
        }
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $t)) {
            return $t;
        }

        return '';
    }

    public function saveMailEnv(Request $request): JsonResponse
    {
        if (! $this->canMail($request)) {
            abort(403, 'Not allowed to update mail settings.');
        }

        $allowedMailers = ['log', 'array', 'smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'resend', 'failover', 'roundrobin'];

        $validated = $request->validate([
            'mail_mailer' => ['required', 'string', Rule::in($allowedMailers)],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_encryption' => ['nullable', 'string', Rule::in(['', 'tls', 'ssl'])],
            'mail_from_address' => ['required', 'string', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        $service = app(MailEnvFileService::class);
        $beforeEnv = $service->read();

        $password = $beforeEnv['MAIL_PASSWORD'] ?? '';
        if ($request->filled('mail_password')) {
            $password = $validated['mail_password'];
        }

        $port = $validated['mail_port'] ?? null;

        try {
            $service->write([
                'MAIL_MAILER' => $validated['mail_mailer'],
                'MAIL_HOST' => $validated['mail_host'] ?? '',
                'MAIL_PORT' => $port !== null ? (string) $port : '',
                'MAIL_USERNAME' => $validated['mail_username'] ?? '',
                'MAIL_PASSWORD' => $password,
                'MAIL_ENCRYPTION' => (string) ($validated['mail_encryption'] ?? ''),
                'MAIL_FROM_ADDRESS' => $validated['mail_from_address'],
                'MAIL_FROM_NAME' => $validated['mail_from_name'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Could not update mail settings.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mail configuration saved.',
        ]);
    }

    public function sendTestMail(Request $request): JsonResponse
    {
        if (! $this->canMail($request)) {
            abort(403, 'Not allowed to send test mail.');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);

        app(MailEnvFileService::class)->applyToConfig();

        $to = $validated['email'];
        $appName = (string) (Setting::get('site_name') ?: config('app.name', 'Guides'));

        try {
            Mail::raw(
                "This is a test email from {$appName}.\n\nSent from Admin → Settings → Mail configuration.\nTime: ".now()->toDateTimeString(),
                function ($message) use ($to, $appName) {
                    $message->to($to)->subject("Mail test — {$appName}");
                }
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Could not send test email. Check mail settings and storage/logs/laravel.log.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test email sent to '.$to.'.',
        ]);
    }

    public function saveMsg91Templates(Request $request): JsonResponse
    {
        if (! $this->canSms($request)) {
            abort(403, 'Not allowed to update SMS templates.');
        }

        $rows = $request->input('templates', []);
        if (! is_array($rows)) {
            return response()->json(['success' => false, 'message' => 'Invalid payload.'], 422);
        }

        $map = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = isset($row['key']) ? trim((string) $row['key']) : '';
            $id = isset($row['id']) ? trim((string) $row['id']) : '';
            if ($key === '' && $id === '') {
                continue;
            }
            if ($key === '' || $id === '') {
                return response()->json(['success' => false, 'message' => 'Each row must have both template key and template ID.'], 422);
            }
            if (! preg_match('/^[a-z0-9_]+$/', $key)) {
                return response()->json(['success' => false, 'message' => 'Template keys may only use lowercase letters, numbers, and underscores: '.$key], 422);
            }
            $map[$key] = $id;
        }

        Setting::put('msg91_templates', json_encode($map));

        return response()->json([
            'success' => true,
            'message' => 'Templates saved.',
            'templates' => $map,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeSettingsStoreKeys($request);

        $except = ['_token', '_method', 'msg91_auth_key', 'msg91_ignored_username'];
        $posted = $request->except($except);
        if ($request->filled('msg91_auth_key')) {
            $posted['msg91_auth_key'] = $request->string('msg91_auth_key')->toString();
        }

        try {
            $themeMetaKeysList = ThemeModeCssOverrides::optionKeys();

            foreach ($posted as $key => $value) {
                $val = is_array($value) ? json_encode($value) : $value;
                if (in_array($key, $themeMetaKeysList, true)) {
                    $val = ThemeModeCssOverrides::sanitizeStoredValue($key, (string) $val);
                } elseif ($key === 'main_nav_bg') {
                    $val = self::sanitizeMainNavBgOption((string) $val);
                } elseif (in_array($key, PostEditorSettingsService::optionKeys(), true)) {
                    $val = (string) PostEditorSettingsService::sanitizeOptionValue($key, $val);
                }
                Setting::put((string) $key, (string) $val);
            }

            $this->syncBrandingAliases($posted);

            $themeTouched = array_intersect(array_keys($posted), $themeMetaKeysList) !== []
                || array_key_exists('main_nav_bg', $posted);
            if ($themeTouched) {
                $all = Setting::query()->pluck('value', 'key');
                Setting::put('theme_mode_css', ThemeModeCssOverrides::buildCss($all));
            }
            Cache::forget('guides.site_popup');
        } catch (\Throwable $e) {
            report($e);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug') ? $e->getMessage() : 'Could not save settings. Please try again.',
                ], 500);
            }

            return redirect()->route('administrator.settings.edit')->with('error', 'Could not save settings.');
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Settings saved successfully.']);
        }

        return redirect()->route('administrator.settings.edit')->with('success', 'Settings saved successfully.');
    }

    private function syncBrandingAliases(array $posted): void
    {
        if (array_key_exists('websitetitle', $posted)) {
            Setting::put('site_name', (string) $posted['websitetitle']);
        }
        $favicon = null;
        if (array_key_exists('favicon_image_url', $posted)) {
            $favicon = trim((string) $posted['favicon_image_url']);
            Setting::put('favicon_url', $favicon);
        } else {
            $favicon = trim((string) (Setting::get('favicon_image_url') ?: Setting::get('favicon_url') ?: ''));
        }

        if (array_key_exists('sidebar_logo_dark_lg_url', $posted)) {
            $lg = trim((string) $posted['sidebar_logo_dark_lg_url']);
            Setting::put('logo_lg_url', $lg);
            if ($lg !== '') {
                Setting::put('logo_url', $lg);
            }
        }

        if (array_key_exists('sidebar_logo_dark_sm_url', $posted)) {
            Setting::put('logo_sm_url', trim((string) $posted['sidebar_logo_dark_sm_url']));
        }

        // Favicon doubles as the main icon when small/main logos are empty.
        if ($favicon !== '') {
            foreach (['sidebar_logo_dark_sm_url', 'sidebar_logo_light_sm_url', 'logo_sm_url'] as $key) {
                if (trim((string) (Setting::get($key) ?? '')) === '') {
                    Setting::put($key, $favicon);
                }
            }
            if (trim((string) (Setting::get('logo_url') ?? '')) === ''
                && trim((string) (Setting::get('sidebar_logo_dark_lg_url') ?? '')) === '') {
                Setting::put('logo_url', $favicon);
            }
        }
    }

    private function authorizeSettingsStoreKeys(Request $request): void
    {
        $user = $request->user();
        $except = ['_token', '_method', 'msg91_auth_key', 'msg91_ignored_username'];
        $keys = array_keys($request->except($except));
        if ($request->filled('msg91_auth_key')) {
            $keys[] = 'msg91_auth_key';
        }
        $keys = array_values(array_unique($keys));

        if ($keys === []) {
            abort(422, 'No settings submitted.');
        }

        $canAll = $user->can('settings.edit') || $user->can('settings.manage');
        $smsPosted = array_values(array_intersect($keys, self::SMS_SETTINGS_OPTION_KEYS));
        $postPosted = array_values(array_intersect($keys, PostEditorSettingsService::optionKeys()));
        $themeMetaPosted = array_values(array_intersect($keys, ThemeModeCssOverrides::optionKeys()));
        $popupPosted = array_values(array_filter($keys, fn ($k) => str_starts_with((string) $k, 'popup_')));
        $nonSmsNonThemeNonPost = array_values(array_diff(
            $keys,
            self::SMS_SETTINGS_OPTION_KEYS,
            PostEditorSettingsService::optionKeys(),
            ThemeModeCssOverrides::optionKeys(),
            $popupPosted,
        ));

        if ($smsPosted !== [] && ! $canAll && ! $user->can('settings.sms')) {
            abort(403, 'Not allowed to update SMS settings.');
        }
        if ($postPosted !== [] && ! $canAll && ! $user->can('settings.post')) {
            abort(403, 'Not allowed to update post settings.');
        }
        if ($themeMetaPosted !== [] && ! $canAll && ! $user->can('settings.theme')) {
            abort(403, 'Not allowed to update theme mode settings.');
        }
        if ($popupPosted !== [] && ! $canAll && ! $user->can('settings.popup')) {
            abort(403, 'Not allowed to update popup settings.');
        }

        if ($nonSmsNonThemeNonPost === []) {
            return;
        }

        if ($nonSmsNonThemeNonPost === ['main_nav_bg']) {
            if (! $canAll && ! $user->can('settings.site') && ! $user->can('settings.theme')) {
                abort(403, 'Not allowed to update this setting.');
            }

            return;
        }

        if (! $canAll && ! $user->can('settings.site')) {
            abort(403, 'Not allowed to update site settings.');
        }
    }

    private function canMail(Request $request): bool
    {
        $user = $request->user();

        return $user->can('settings.edit') || $user->can('settings.manage') || $user->can('settings.mail');
    }

    private function canSms(Request $request): bool
    {
        $user = $request->user();

        return $user->can('settings.edit') || $user->can('settings.manage') || $user->can('settings.sms');
    }
}
