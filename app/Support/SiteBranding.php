<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SiteBranding
{
    public static function forView(): array
    {
        try {
            $opts = collect(Setting::query()->pluck('value', 'key')->all());
        } catch (\Throwable $e) {
            $opts = collect();
        }

        $name = self::str($opts, 'websitetitle') ?: self::str($opts, 'site_name');
        if ($name === '') {
            $name = (string) config('app.name', 'Ahmedabad Guides');
        }

        $defaultIcon = (string) asset('favicon.svg');
        $favicon = self::str($opts, 'favicon_image_url')
            ?: self::str($opts, 'favicon_url')
            ?: $defaultIcon;

        // Favicon is the main icon; large logos are optional wordmarks.
        $sm = self::str($opts, 'sidebar_logo_dark_sm_url')
            ?: self::str($opts, 'logo_sm_url')
            ?: $favicon;
        $lgExplicit = self::str($opts, 'sidebar_logo_dark_lg_url')
            ?: self::str($opts, 'logo_lg_url')
            ?: self::str($opts, 'logo_url');
        $hasWordmark = $lgExplicit !== '' && $lgExplicit !== $sm && $lgExplicit !== $favicon;
        $lg = $hasWordmark ? $lgExplicit : $sm;

        $lightSm = self::str($opts, 'sidebar_logo_light_sm_url') ?: $sm;
        $lightLgExplicit = self::str($opts, 'sidebar_logo_light_lg_url');
        $lightHasWordmark = $lightLgExplicit !== '' && $lightLgExplicit !== $lightSm && $lightLgExplicit !== $favicon;
        $lightLg = $lightHasWordmark ? $lightLgExplicit : ($hasWordmark ? $lg : $lightSm);

        $authDarkOverride = self::str($opts, 'auth_logo_dark_url');
        $authLightOverride = self::str($opts, 'auth_logo_light_url');
        $authDark = $authDarkOverride !== '' ? $authDarkOverride : $lg;
        $authLight = $authLightOverride !== '' ? $authLightOverride : $lightLg;

        $logoLgPx = self::logoHeightPx($opts, 'logo_lg_height', 30);
        $logoSmPx = self::logoHeightPx($opts, 'logo_sm_height', 28);
        $authLogoPx = self::logoHeightPx($opts, 'auth_logo_height', 32);
        $mainNavBg = self::sanitizedMainNavBg($opts);

        $out = [
            'name' => $name,
            'short_name' => self::str($opts, 'site_short_name') ?: 'Guides',
            'favicon' => $favicon,
            // Show site title next to icon when no dedicated large/wordmark logo is set.
            'show_name' => ! $hasWordmark,
            'sidebar' => [
                'dark_sm' => $sm,
                'dark_lg' => $lg,
                'light_sm' => $lightSm,
                'light_lg' => $lightLg,
            ],
            'auth' => [
                'dark' => $authDark,
                'light' => $authLight,
                'height_px' => $authLogoPx,
            ],
            'logo_lg_height' => $logoLgPx.'px',
            'logo_sm_height' => $logoSmPx.'px',
            'theme_mode_css' => ThemeModeCssOverrides::buildCss($opts),
        ];

        if ($mainNavBg !== null) {
            $out['main_nav_bg'] = $mainNavBg;
        }

        return $out;
    }

    private static function sanitizedMainNavBg(Collection $opts): ?string
    {
        $raw = trim(self::str($opts, 'main_nav_bg'));
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $raw)) {
            return $raw;
        }

        return null;
    }

    private static function logoHeightPx(Collection $opts, string $key, int $default): int
    {
        if (! isset($opts[$key]) || trim((string) $opts[$key]) === '') {
            return $default;
        }
        $n = (int) preg_replace('/\D+/', '', (string) $opts[$key]);
        if ($n < 12) {
            return $default;
        }
        if ($n > 200) {
            return 200;
        }

        return $n;
    }

    private static function str(Collection $opts, string $key): string
    {
        if (! isset($opts[$key])) {
            return '';
        }

        return trim((string) $opts[$key]);
    }
}
