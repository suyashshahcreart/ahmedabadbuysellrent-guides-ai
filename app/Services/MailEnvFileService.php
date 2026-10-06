<?php

namespace App\Services;

use App\Models\Setting;

class MailEnvFileService
{
    public const KEYS = [
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
    ];

    public function read(): array
    {
        $out = array_fill_keys(self::KEYS, '');
        foreach (self::KEYS as $key) {
            $value = Setting::get(strtolower($key));
            if ($value === null || $value === '') {
                $env = env($key);
                $out[$key] = $env !== null ? (string) $env : '';
            } else {
                $out[$key] = (string) $value;
            }
        }

        return $out;
    }

    public function write(array $pairs): void
    {
        foreach (array_keys($pairs) as $key) {
            if (! in_array($key, self::KEYS, true)) {
                throw new \InvalidArgumentException('Invalid mail configuration key: '.$key);
            }
        }
        foreach ($pairs as $key => $value) {
            Setting::put(strtolower($key), (string) $value);
        }
        $this->applyToConfig($pairs);
    }

    public function applyToConfig(?array $pairs = null): void
    {
        $pairs = $pairs ?? $this->read();
        $mailer = $pairs['MAIL_MAILER'] ?: config('mail.default');
        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.host' => $pairs['MAIL_HOST'] ?: config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => $pairs['MAIL_PORT'] !== '' ? (int) $pairs['MAIL_PORT'] : config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username' => $pairs['MAIL_USERNAME'] ?: config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => $pairs['MAIL_PASSWORD'] !== '' ? $pairs['MAIL_PASSWORD'] : config('mail.mailers.smtp.password'),
            'mail.mailers.smtp.encryption' => $pairs['MAIL_ENCRYPTION'] !== '' ? $pairs['MAIL_ENCRYPTION'] : config('mail.mailers.smtp.encryption'),
            'mail.from.address' => $pairs['MAIL_FROM_ADDRESS'] ?: config('mail.from.address'),
            'mail.from.name' => $pairs['MAIL_FROM_NAME'] ?: config('mail.from.name'),
        ]);
    }
}
