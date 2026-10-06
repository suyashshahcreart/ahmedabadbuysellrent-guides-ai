<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Insight;
use App\Models\Locality;
use App\Models\MediaAsset;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

class ImportStaticCommand extends Command
{
    protected $signature = 'guides:import-static {--force}';

    protected $description = 'Import archived Eleventy content into the CMS';

    public function handle(): int
    {
        $root = base_path('_archive/eleventy/src');
        if (! is_dir($root)) {
            $this->error('Archive not found at '.$root);

            return self::FAILURE;
        }

        $this->importSettings();
        $this->importMenus();
        $author = Author::firstOrCreate(['slug' => 'ahmedabad-guides'], ['name' => 'Ahmedabad Guides', 'status' => true]);
        $this->importCategories($root);
        $mediaMap = $this->importPhotos($root);
        $this->importArticles($root.'/guides', 'guide', $author, $mediaMap);
        $this->importArticles($root.'/compare', 'compare', $author, $mediaMap);
        $this->importLocalities($root, $mediaMap);
        $this->importInsights($root);
        $this->importAbout($root);
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function importSettings(): void
    {
        $pairs = [
            'site_name' => 'Ahmedabad Property Guides',
            'site_short_name' => 'Ahmedabad Guides',
            'site_url' => 'https://guides.ahmedabadbuysellrent.in',
            'default_seo_title' => 'Ahmedabad Property Guides',
            'default_meta_description' => 'Your trusted guide to buying, selling and renting property in Ahmedabad. Locality reviews, comparisons, and practical advice.',
            'cta_url' => '',
            'cta_label' => 'Explore guides',
            'copyright' => 'Ahmedabad Property Guides',
        ];
        foreach ($pairs as $k => $v) {
            Setting::put($k, $v);
        }
    }

    private function importMenus(): void
    {
        $header = Menu::firstOrCreate(['location' => 'header'], ['name' => 'Header']);
        $footer = Menu::firstOrCreate(['location' => 'footer'], ['name' => 'Footer']);
        if ($header->allItems()->count() === 0) {
            foreach ([
                ['Buy', '/buy/', 1], ['Rent', '/rent/', 2], ['Sell', '/sell/', 3],
                ['Areas', '/areas/', 4], ['Compare', '/compare/', 5], ['All guides', '/guides/', 6],
            ] as $i) {
                $header->allItems()->create(['label' => $i[0], 'url' => $i[1], 'sort_order' => $i[2], 'status' => true, 'target' => '_self']);
            }
        }
        if ($footer->allItems()->count() === 0) {
            $footer->allItems()->create(['label' => 'About', 'url' => '/about/', 'sort_order' => 1, 'status' => true, 'target' => '_self']);
        }
    }

    private function importCategories(string $root): void
    {
        $json = json_decode(File::get($root.'/_data/categories.json'), true) ?? [];
        foreach ($json as $i => $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], [
                'name' => $cat['title'],
                'nav_label' => $cat['nav'] ?? $cat['title'],
                'tag_label' => $cat['tag'] ?? $cat['title'],
                'icon' => $cat['icon'] ?? null,
                'description' => $cat['description'] ?? '',
                'sort_order' => $i,
                'status' => 'published',
            ]);
        }
    }

    private function importPhotos(string $root): array
    {
        $map = [];
        $dir = $root.'/assets/photos';
        if (! is_dir($dir)) {
            return $map;
        }
        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();
            $dest = 'media/imported/'.$name;
            Storage::disk('public')->put($dest, File::get($file->getPathname()));
            $asset = MediaAsset::firstOrCreate(['path' => $dest], [
                'original_name' => $name,
                'mime_type' => 'image/jpeg',
                'alt' => Str::headline(pathinfo($name, PATHINFO_FILENAME)),
            ]);
            $map['/assets/photos/'.$name] = $asset->id;
        }

        return $map;
    }

    private function importArticles(string $dir, string $type, Author $author, array $mediaMap): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $md = new CommonMarkConverter();
        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }
            [$fm, $body] = $this->frontMatter($file->getContents());
            $slug = trim((string) ($fm['permalink'] ?? $file->getFilenameWithoutExtension()), '/');
            $slug = basename($slug);
            $html = $md->convert($body)->__toString();
            $cat = Category::where('slug', $fm['category'] ?? ($type === 'compare' ? 'compare' : 'buy'))->first();
            $path = $type === 'compare' ? 'compare/'.$slug.'/' : 'guides/'.$slug.'/';
            $image = $fm['image'] ?? null;
            Article::updateOrCreate(['path' => $path], [
                'type' => $type,
                'title' => $fm['title'] ?? $slug,
                'slug' => $slug,
                'excerpt' => $fm['description'] ?? '',
                'content' => $html,
                'featured_media_id' => $image && isset($mediaMap[$image]) ? $mediaMap[$image] : null,
                'author_id' => $author->id,
                'category_id' => $cat?->id,
                'status' => 'published',
                'published_at' => $fm['date'] ?? now(),
                'featured' => ! empty($fm['featured']),
                'trending' => ! empty($fm['trending']),
                'reading_time' => $fm['readingTime'] ?? max(1, (int) ceil(str_word_count(strip_tags($html)) / 200)),
            ]);
        }
    }

    private function importLocalities(string $root, array $mediaMap): void
    {
        $zones = json_decode(File::get($root.'/_data/zones.json'), true) ?? [];
        $md = new CommonMarkConverter();
        foreach ($zones as $i => $z) {
            $file = $root.'/areas/'.$z['slug'].'.md';
            $overview = '';
            if (is_file($file)) {
                [, $body] = $this->frontMatter(File::get($file));
                $overview = $md->convert($body)->__toString();
            }
            Locality::updateOrCreate(['slug' => $z['slug']], [
                'name' => $z['name'],
                'short' => $z['short'] ?? '',
                'tags' => $z['tags'] ?? [],
                'summary' => $z['summary'] ?? '',
                'overview' => $overview,
                'featured_media_id' => isset($mediaMap[$z['image'] ?? '']) ? $mediaMap[$z['image']] : null,
                'sort_order' => $i + 1,
                'status' => 'published',
                'published_at' => now(),
            ]);
        }
    }

    private function importInsights(string $root): void
    {
        $items = json_decode(File::get($root.'/_data/insights.json'), true) ?? [];
        foreach ($items as $i => $row) {
            Insight::updateOrCreate(['slug' => Str::slug($row['title'])], [
                'title' => $row['title'],
                'stat' => $row['stat'] ?? '',
                'label' => $row['label'] ?? '',
                'body' => $row['body'] ?? '',
                'status' => 'published',
                'published_at' => now(),
                'sort_order' => $i,
            ]);
        }
    }

    private function importAbout(string $root): void
    {
        $file = $root.'/about.njk';
        $html = '<p>Practical guides for buying, renting and selling in Ahmedabad; zone and locality notes; and side-by-side comparisons of neighborhoods.</p>';
        if (is_file($file)) {
            $raw = File::get($file);
            [, $body] = $this->frontMatter($raw);
            $html = strip_tags($body, '<p><h2><a><em><strong>');
        }
        Page::updateOrCreate(['slug' => 'about'], [
            'title' => 'About Ahmedabad Property Guides',
            'path' => 'about/',
            'excerpt' => 'How Ahmedabad Guides helps buyers, renters and sellers understand localities, documents and decisions.',
            'content' => $html,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function frontMatter(string $raw): array
    {
        if (! str_starts_with($raw, '---')) {
            return [[], $raw];
        }
        $parts = preg_split('/^---\s*$/m', $raw, 3);
        $yaml = $parts[1] ?? '';
        $body = $parts[2] ?? $raw;
        $fm = [];
        foreach (preg_split('/\n/', trim($yaml)) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $v = trim($v);
            $v = trim($v, '"\'');
            if ($v === 'true') {
                $v = true;
            } elseif ($v === 'false') {
                $v = false;
            }
            $fm[trim($k)] = $v;
        }

        return [$fm, ltrim($body)];
    }
}
