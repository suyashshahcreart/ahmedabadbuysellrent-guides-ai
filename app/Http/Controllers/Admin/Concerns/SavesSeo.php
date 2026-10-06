<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\MediaAsset;
use App\Services\SchemaSync;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait SavesSeo
{
    protected function saveSeo($model, Request $request): void
    {
        $payload = $request->only([
            'seo_title', 'meta_description', 'canonical_url', 'robots',
            'seo_keywords', 'og_title', 'og_description', 'og_image',
            'og_image_media_id', 'twitter_title', 'twitter_description', 'twitter_image',
        ]);
        if ($request->filled('seo_description') && empty($payload['meta_description'])) {
            $payload['meta_description'] = $request->input('seo_description');
        }
        if (! empty($payload['og_image_media_id'])) {
            $asset = MediaAsset::find($payload['og_image_media_id']);
            if ($asset) {
                $payload['og_image'] = $asset->url();
            }
        }
        $model->seo()->updateOrCreate([], array_filter($payload, fn ($v) => $v !== null));
        app(SchemaSync::class)->sync($model);
    }

    protected function saveFaqs($model, Request $request): void
    {
        if (! method_exists($model, 'faqs')) {
            return;
        }
        $model->faqs()->delete();
        $sanitizer = app(\App\Services\HtmlSanitizer::class);
        foreach ($request->input('faqs', []) as $i => $faq) {
            $question = trim((string) ($faq['question'] ?? ''));
            $answer = trim((string) ($faq['answer'] ?? ''));
            if ($question === '' && $answer === '') {
                continue;
            }
            if ($question === '') {
                continue;
            }
            $active = array_key_exists('is_active', $faq)
                ? ! empty($faq['is_active'])
                : ! empty($faq['status']);
            $model->faqs()->create([
                'question' => $question,
                'answer' => $sanitizer->clean($answer),
                'sort_order' => isset($faq['sort_order']) ? (int) $faq['sort_order'] : $i,
                'status' => $active,
            ]);
        }
    }

    protected function slugify(string $value): string
    {
        return Str::slug($value);
    }
}
