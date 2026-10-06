<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedGuideTagsCommand extends Command
{
    protected $signature = 'guides:seed-tags {--force : Re-sync tags even if articles already have tags}';

    protected $description = 'Create content-wise tags and attach them to all guide articles';

    public function handle(): int
    {
        $catalog = [
            'Ahmedabad' => 'City-level property guides for Ahmedabad',
            'Home Loan' => 'Home loan and financing guides',
            'First-time Buyer' => 'Advice for first-time property buyers',
            'Property Market' => 'Market trends and demand notes',
            'Buy vs Rent' => 'Buying versus renting decisions',
            'Investment' => 'Property investment strategy',
            'NRI' => 'NRI property purchase and compliance',
            'Documents' => 'Property paperwork and legal docs',
            'Selling' => 'Selling a property in Ahmedabad',
            'Renting' => 'Rental locality and tenant guides',
            'Locality Guide' => 'Area-focused living and buying guides',
            'West Zone' => 'West Ahmedabad localities',
            'Central Zone' => 'Central / walled city living',
            'Thaltej' => 'Thaltej locality content',
            'Bodakdev' => 'Bodakdev locality content',
            'Satellite' => 'Satellite locality content',
            'Gota' => 'Gota rental and living content',
            'Navrangpura' => 'Navrangpura rental and living content',
            'SG Highway' => 'SG Highway corridor content',
            'CG Road' => 'CG Road corridor content',
            'Comparison' => 'Head-to-head locality or corridor comparisons',
            'Interiors' => 'Apartment interiors and fit-out checks',
            'Checklist' => 'Practical buyer / seller checklists',
        ];

        $tagIds = [];
        foreach ($catalog as $name => $description) {
            $tag = Tag::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'status' => 'published',
                    'sort_order' => 0,
                ]
            );
            $tagIds[$name] = $tag->id;
            $this->line('Tag: '.$name);
        }

        $map = [
            'ahmedabad-home-loan-guide' => ['Ahmedabad', 'Home Loan', 'First-time Buyer', 'Checklist'],
            'ahmedabad-property-market-notes' => ['Ahmedabad', 'Property Market', 'Investment'],
            'buy-vs-rent-ahmedabad' => ['Ahmedabad', 'Buy vs Rent', 'First-time Buyer', 'Investment'],
            'central-zone-living' => ['Ahmedabad', 'Locality Guide', 'Central Zone'],
            'evaluating-apartment-interiors' => ['Ahmedabad', 'Interiors', 'First-time Buyer', 'Checklist'],
            'first-time-buyer-checklist' => ['Ahmedabad', 'First-time Buyer', 'Checklist', 'Documents'],
            'investing-in-ahmedabad-property' => ['Ahmedabad', 'Investment', 'Property Market'],
            'new-west-zone' => ['Ahmedabad', 'Locality Guide', 'West Zone'],
            'nri-property-ahmedabad' => ['Ahmedabad', 'NRI', 'Investment', 'Documents'],
            'property-documents-ahmedabad' => ['Ahmedabad', 'Documents', 'Checklist', 'First-time Buyer'],
            'renting-gota' => ['Ahmedabad', 'Renting', 'Locality Guide', 'Gota', 'West Zone'],
            'renting-navrangpura' => ['Ahmedabad', 'Renting', 'Locality Guide', 'Navrangpura', 'Central Zone'],
            'selling-property-ahmedabad' => ['Ahmedabad', 'Selling', 'Documents', 'Checklist'],
            'thaltej-buying-guide' => ['Ahmedabad', 'Locality Guide', 'Thaltej', 'West Zone', 'First-time Buyer'],
            'west-zone-overview' => ['Ahmedabad', 'Locality Guide', 'West Zone'],
            'bodakdev-vs-satellite' => ['Ahmedabad', 'Comparison', 'Bodakdev', 'Satellite', 'West Zone'],
            'sg-highway-vs-cg-road' => ['Ahmedabad', 'Comparison', 'SG Highway', 'CG Road'],
            'thaltej-vs-bodakdev' => ['Ahmedabad', 'Comparison', 'Thaltej', 'Bodakdev', 'West Zone'],
        ];

        $synced = 0;
        foreach ($map as $slug => $names) {
            $article = Article::query()->where('slug', $slug)->first();
            if (! $article) {
                $this->warn('Missing article: '.$slug);
                continue;
            }
            if (! $this->option('force') && $article->tags()->exists()) {
                $this->line('Skip (already tagged): '.$slug);
                continue;
            }
            $ids = collect($names)->map(fn ($n) => $tagIds[$n] ?? null)->filter()->values()->all();
            $article->tags()->sync($ids);
            $synced++;
            $this->info('Tagged '.$slug.' → '.implode(', ', $names));
        }

        $this->info("Done. Tags=".count($tagIds)." articles_synced={$synced}");

        return self::SUCCESS;
    }
}
