<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\SchemaSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpgradeGuideOnPageSeoCommand extends Command
{
    protected $signature = 'guides:upgrade-onpage-seo {--slug= : Upgrade only one slug}';

    protected $description = 'Rewrite all guide articles with 1200–1500 word SEO content, meta, FAQs and schema';

    public function handle(SchemaSync $schemaSync): int
    {
        $path = storage_path('app/guide-onpage-seo.json');
        if (! File::exists($path)) {
            $this->error('Missing content pack: storage/app/guide-onpage-seo.json');

            return self::FAILURE;
        }

        $all = json_decode(File::get($path), true);
        if (! is_array($all) || $all === []) {
            $this->error('Invalid content pack JSON.');

            return self::FAILURE;
        }

        $only = $this->option('slug');
        if ($only) {
            if (! isset($all[$only])) {
                $this->error('Unknown slug: '.$only);

                return self::FAILURE;
            }
            $all = [$only => $all[$only]];
        }

        $bar = $this->output->createProgressBar(count($all));
        $bar->start();

        foreach ($all as $slug => $def) {
            $article = Article::query()->where('slug', $slug)->first();
            if (! $article) {
                $this->newLine();
                $this->warn("Missing article: {$slug}");
                $bar->advance();
                continue;
            }

            $html = (string) ($def['content_html'] ?? '');
            $words = str_word_count(strip_tags($html));

            $article->fill([
                'title' => $def['title'],
                'excerpt' => $def['excerpt'],
                'content' => $html,
                'reading_time' => max(1, (int) ceil($words / 220)),
                'status' => 'published',
                'published_at' => $article->published_at ?: now(),
            ])->save();

            $article->seo()->updateOrCreate([], [
                'seo_title' => $def['seo_title'],
                'meta_description' => $def['meta_description'] ?? $def['excerpt'],
                'seo_keywords' => $def['keywords'] ?? null,
                'canonical_url' => $article->publicUrl(),
                'robots' => 'index, follow',
                'og_title' => $def['seo_title'],
                'og_description' => $def['meta_description'] ?? $def['excerpt'],
                'twitter_title' => $def['seo_title'],
                'twitter_description' => $def['meta_description'] ?? $def['excerpt'],
            ]);

            $article->faqs()->delete();
            foreach (array_values($def['faqs'] ?? []) as $i => $faq) {
                $article->faqs()->create([
                    'question' => $faq['q'],
                    'answer' => $faq['a'],
                    'sort_order' => $i + 1,
                    'status' => true,
                ]);
            }

            $article->load('faqs');
            $schemaSync->sync($article);

            $this->newLine();
            $this->line(sprintf('  %s: %d words, %d FAQs, SEO+schema synced', $slug, $words, count($def['faqs'] ?? [])));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('On-page SEO content upgrade complete.');

        return self::SUCCESS;
    }
}
