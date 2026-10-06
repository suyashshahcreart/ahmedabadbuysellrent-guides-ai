<?php

namespace App\Support;

use Illuminate\Support\Collection;

final class ThemeModeCssOverrides
{
    /**
     * @return list<string>
     */
    public static function optionKeys(): array
    {
        return array_keys(config('theme-mode-defaults.meta', []));
    }

    /**
     * Merged theme CSS (defaults + options), each declaration !important to override compiled SCSS.
     */
    public static function buildCss(Collection $optionValues): string
    {
        /** @var array<string, array{0: string, 1: string, 2: string}> $meta */
        $meta = config('theme-mode-defaults.meta', []);
        $bySelector = [];
        $globalMainNavBg = trim((string) ($optionValues->get('main_nav_bg') ?? ''));
        $skipMainNavBgPresets = $globalMainNavBg !== '' && self::isValidMainNavBgHex($globalMainNavBg);

        foreach ($meta as $optionKey => $tuple) {
            [$selector, $prop, $default] = $tuple;
            if ($prop === '--bs-main-nav-bg' && $skipMainNavBgPresets) {
                continue;
            }
            $raw = trim((string) ($optionValues->get($optionKey) ?? ''));
            $value = $raw !== ''
                ? self::sanitizeValue($prop, $raw)
                : $default;
            if ($value === '') {
                $value = $default;
            }
            if (! isset($bySelector[$selector])) {
                $bySelector[$selector] = [];
            }
            $bySelector[$selector][$prop] = $value;
        }

        $parts = [];
        foreach ($bySelector as $selector => $vars) {
            $decl = [];
            foreach ($vars as $prop => $val) {
                $decl[] = $prop.': '.$val.' !important;';
            }
            $parts[] = $selector." {\n    ".implode("\n    ", $decl)."\n}";
        }

        return implode("\n\n", $parts);
    }

    private static function isValidMainNavBgHex(string $value): bool
    {
        return (bool) preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', trim($value));
    }

    /**
     * Sanitize posted values before persisting to options.
     */
    public static function sanitizeStoredValue(string $optionKey, string $raw): string
    {
        $meta = config('theme-mode-defaults.meta', []);
        if (! isset($meta[$optionKey])) {
            return '';
        }
        [, $prop, $default] = $meta[$optionKey];
        $t = trim($raw);
        if ($t === '') {
            return '';
        }
        $v = self::sanitizeValue($prop, $t);

        return $v !== '' ? $v : '';
    }

    private static function sanitizeValue(string $cssProperty, string $raw): string
    {
        $t = trim($raw);
        if ($t === '') {
            return '';
        }

        $isDimension = str_contains($cssProperty, 'height')
            || str_contains($cssProperty, 'width')
            || str_contains($cssProperty, '-size')
            || str_contains($cssProperty, 'padding')
            || str_contains($cssProperty, 'margin');

        if ($isDimension) {
            $n = preg_replace('/[^\d.]/', '', $t);
            if ($n === '' || ! is_numeric($n)) {
                return '';
            }
            $num = (float) $n;
            if ($num < 0 || $num > 9999) {
                return '';
            }

            return round($num).'px';
        }

        if (preg_match('/^rgba\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[\d.]+\s*)?\)$/i', $t)) {
            return preg_replace('/\s*,\s*/', ', ', preg_replace('/\s+/', ' ', $t));
        }

        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $t)) {
            return $t;
        }

        return '';
    }
}
