<?php

namespace App\Services;

use Mews\Purifier\Facades\Purifier;

class HtmlSanitizer
{
    public function clean(?string $html): string
    {
        if (! $html) {
            return '';
        }

        return Purifier::clean($html);
    }

    public function readingMinutes(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / 200));
    }
}
