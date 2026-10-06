<?php

namespace App\Support;

class PermissionCatalog
{
    public static function all(): array
    {
        return array_merge(
            self::legacyAliases(),
            self::content(),
            self::site(),
            self::system(),
            self::ai(),
            self::seo(),
        );
    }

    public static function groups(): array
    {
        return [
            'Content' => self::content(),
            'Site' => self::site(),
            'System' => self::system(),
            'AI' => self::ai(),
            'SEO' => self::seo(),
        ];
    }

    public static function content(): array
    {
        $out = [];
        foreach (['articles' => ['view', 'create', 'edit', 'delete', 'publish'], 'pages' => ['view', 'create', 'edit', 'delete'], 'categories' => ['view', 'create', 'edit', 'delete'], 'tags' => ['view', 'create', 'edit', 'delete'], 'authors' => ['view', 'create', 'edit', 'delete'], 'localities' => ['view', 'create', 'edit', 'delete'], 'insights' => ['view', 'create', 'edit', 'delete'], 'faqs' => ['view', 'create', 'edit', 'delete'], 'media' => ['view', 'upload', 'edit', 'delete']] as $mod => $acts) {
            foreach ($acts as $act) {
                $out[] = $mod.'.'.$act;
            }
        }

        return $out;
    }

    public static function site(): array
    {
        return ['menus.view', 'menus.edit', 'settings.view', 'settings.edit', 'settings.site', 'settings.sms', 'settings.mail', 'settings.theme', 'settings.post', 'settings.popup', 'activity.view'];
    }

    public static function system(): array
    {
        $out = [];
        foreach (['users', 'roles', 'permissions'] as $mod) {
            foreach (['view', 'create', 'edit', 'delete'] as $act) {
                $out[] = $mod.'.'.$act;
            }
        }

        return $out;
    }

    public static function ai(): array
    {
        return [
            'ai.credits.view',
            'ai.credits.manage',
            'ai.automation.view',
            'ai.automation.manage',
        ];
    }

    public static function seo(): array
    {
        return [
            'seo.view', 'seo.create', 'seo.edit', 'seo.delete',
            'seo.templates.manage',
            'seo.redirects.view', 'seo.redirects.create', 'seo.redirects.delete',
            'seo.schemas.view',
            'seo.sitemap.view', 'seo.sitemap.edit',
        ];
    }

    public static function legacyAliases(): array
    {
        return [
            'articles.view', 'articles.create', 'articles.edit', 'articles.delete', 'articles.publish',
            'categories.manage', 'tags.manage', 'pages.manage', 'faq.manage', 'media.manage',
            'seo.manage', 'redirects.manage', 'settings.manage', 'users.manage',
        ];
    }

    public static function editor(): array
    {
        return array_merge(self::content(), ['menus.view', 'activity.view']);
    }

    public static function author(): array
    {
        return ['articles.view', 'articles.create', 'articles.edit'];
    }

    public static function seoManager(): array
    {
        return array_merge(self::seo(), ['seo.manage', 'redirects.manage']);
    }
}
