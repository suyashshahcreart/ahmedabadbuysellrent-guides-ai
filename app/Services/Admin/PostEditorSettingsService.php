<?php

namespace App\Services\Admin;

use App\Models\Setting;

class PostEditorSettingsService
{
    public const KEY_AUTOSAVE_DEBOUNCE_SECONDS = 'post_autosave_debounce_seconds';

    public const KEY_LOCK_HEARTBEAT_SECONDS = 'post_lock_heartbeat_seconds';

    public const KEY_LOCK_POLL_SECONDS = 'post_lock_poll_seconds';

    public const KEY_LIST_LOCK_REFRESH_SECONDS = 'post_list_lock_refresh_seconds';

    private const DEFAULTS = [
        self::KEY_AUTOSAVE_DEBOUNCE_SECONDS => 30,
        self::KEY_LOCK_HEARTBEAT_SECONDS => 10,
        self::KEY_LOCK_POLL_SECONDS => 5,
        self::KEY_LIST_LOCK_REFRESH_SECONDS => 15,
    ];

    private const LIMITS = [
        self::KEY_AUTOSAVE_DEBOUNCE_SECONDS => [5, 300],
        self::KEY_LOCK_HEARTBEAT_SECONDS => [5, 120],
        self::KEY_LOCK_POLL_SECONDS => [3, 60],
        self::KEY_LIST_LOCK_REFRESH_SECONDS => [5, 120],
    ];

    public static function optionKeys(): array
    {
        return array_keys(self::DEFAULTS);
    }

    public function allSeconds(): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = $this->seconds($key);
        }

        return $values;
    }

    public function seconds(string $key): int
    {
        if (! isset(self::DEFAULTS[$key], self::LIMITS[$key])) {
            return 0;
        }
        $raw = Setting::get($key);
        $value = is_numeric($raw) ? (int) $raw : self::DEFAULTS[$key];
        [$min, $max] = self::LIMITS[$key];

        return max($min, min($max, $value));
    }

    public static function sanitizeOptionValue(string $key, mixed $value): int
    {
        if (! isset(self::DEFAULTS[$key], self::LIMITS[$key])) {
            return 0;
        }
        $numeric = is_numeric($value) ? (int) $value : self::DEFAULTS[$key];
        [$min, $max] = self::LIMITS[$key];

        return max($min, min($max, $numeric));
    }

    /**
     * @return array{debounceMs: int, heartbeatMs: int, lockPollMs: int}
     */
    public function clientConfig(): array
    {
        return [
            'debounceMs' => $this->seconds(self::KEY_AUTOSAVE_DEBOUNCE_SECONDS) * 1000,
            'heartbeatMs' => $this->seconds(self::KEY_LOCK_HEARTBEAT_SECONDS) * 1000,
            'lockPollMs' => $this->seconds(self::KEY_LOCK_POLL_SECONDS) * 1000,
        ];
    }

    public function listRefreshMs(): int
    {
        return $this->seconds(self::KEY_LIST_LOCK_REFRESH_SECONDS) * 1000;
    }
}
