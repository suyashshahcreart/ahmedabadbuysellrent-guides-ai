<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Cache;

class MediaLibraryUi
{
    public static function typeGroups(): array
    {
        return [
            'image' => [
                ['value' => 'image', 'label' => 'Images only'],
                ['value' => 'image/jpeg', 'label' => 'JPEG'],
                ['value' => 'image/png', 'label' => 'PNG'],
                ['value' => 'image/webp', 'label' => 'WebP'],
                ['value' => 'image/gif', 'label' => 'GIF'],
                ['value' => 'image/svg+xml', 'label' => 'SVG'],
            ],
            'video' => [
                ['value' => 'video', 'label' => 'Videos'],
                ['value' => 'video/mp4', 'label' => 'MP4'],
            ],
            'document' => [
                ['value' => 'application/pdf', 'label' => 'PDF'],
            ],
        ];
    }

    public static function dateOptions(): array
    {
        $ranges = [
            'today' => 'Today',
            'last7days' => 'Last 7 days',
            'last30days' => 'Last 30 days',
            'thismonth' => 'This month',
            'lastmonth' => 'Last month',
        ];

        try {
            $months = Cache::remember('guides.media.month_filters', 60, function () {
                return MediaAsset::query()
                    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
                    ->whereNotNull('created_at')
                    ->groupBy('ym')
                    ->orderByDesc('ym')
                    ->limit(24)
                    ->pluck('ym')
                    ->filter()
                    ->mapWithKeys(function ($ym) {
                        $label = \DateTime::createFromFormat('Y-m', $ym);
                        return [$ym => $label ? $label->format('F Y') : $ym];
                    })
                    ->all();
            });
        } catch (\Throwable $e) {
            $months = [];
        }

        return array_merge($ranges, $months);
    }
}
