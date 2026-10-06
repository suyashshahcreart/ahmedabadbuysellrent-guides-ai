<?php

namespace App\Services\Popup;

use App\Models\Setting;

final class PopupSettingsService
{
    private const PREFIX = 'popup_';

    private const DEFAULTS = [
        'system_enabled' => '1',
        'headline' => '',
        'body' => '',
        'cta_label' => '',
        'cta_url' => '',
        'default_delay_seconds' => '0',
        'default_entry_animation' => 'fadeIn',
        'default_exit_animation' => 'fadeOut',
        'default_overlay_color' => '#000000',
        'default_overlay_opacity' => '0.50',
        'default_width' => '600px',
        'default_close_option' => 'manual',
        'max_per_session' => '3',
        'visitor_id_ttl_days' => '365',
        'analytics_enabled' => '1',
    ];

    public function get(string $key): mixed
    {
        $value = Setting::get(self::PREFIX.$key);

        return $value ?? (self::DEFAULTS[$key] ?? null);
    }

    public function all(): array
    {
        $out = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }
}
