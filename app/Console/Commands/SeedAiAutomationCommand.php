<?php

namespace App\Console\Commands;

use App\Models\AiAngle;
use App\Models\AiGeoNode;
use App\Models\AiSetting;
use App\Models\AiStructureTemplate;
use App\Models\Author;
use App\Models\Category;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedAiAutomationCommand extends Command
{
    protected $signature = 'guides:seed-ai-automation {--fresh-geo : Rebuild geo nodes}';

    protected $description = 'Seed AI automation geo pools, angles, structure templates, editors, settings, and permissions';

    public function handle(): int
    {
        $this->syncPermissions();
        $this->seedSettings();
        $this->seedGeo();
        $this->seedAngles();
        $this->seedTemplates();
        $this->seedEditors();
        $this->info('AI automation seed complete.');

        return self::SUCCESS;
    }

    private function syncPermissions(): void
    {
        foreach (array_unique(PermissionCatalog::all()) as $p) {
            Permission::findOrCreate($p);
        }
        foreach (['superAdmin', 'admin'] as $role) {
            Role::findOrCreate($role)->syncPermissions(Permission::all());
        }
        $this->line('Permissions synced.');
    }

    private function seedSettings(): void
    {
        $areas = Category::where('slug', 'areas')->value('id');
        $settings = AiSetting::current();
        if (! $settings->default_category_id && $areas) {
            $settings->default_category_id = $areas;
            $settings->save();
        }
        $this->line('AI settings ready.');
    }

    private function seedGeo(): void
    {
        if ($this->option('fresh-geo')) {
            AiGeoNode::query()->delete();
        }

        $city = $this->geo('zone', 'Ahmedabad', null, 0);
        $west = $this->geo('zone', 'West Ahmedabad', $city->id, 1);
        $east = $this->geo('zone', 'East Ahmedabad', $city->id, 2);
        $sg = $this->geo('zone', 'SG Highway Corridor', $city->id, 3);

        $westAreas = ['Bopal', 'Shela', 'Satellite', 'Prahladnagar', 'Vastrapur', 'Bodakdev', 'Thaltej', 'Sola', 'Gota', 'Science City', 'Shilaj', 'Ambli', 'Makarba', 'Jodhpur Village', 'Memnagar', 'Naranpura', 'Ranip', 'Chandkheda', 'Jagatpur', 'Tragad', 'Vaishno Devi Circle'];
        $eastAreas = ['Nikol', 'Vastral', 'Naroda', 'Narol', 'Odhav', 'Kathwada', 'Hathijan', 'Maninagar', 'Zundal', 'Motera', 'Gift City', 'Dholera', 'Sanand', 'Changodar', 'Adalaj', 'Kalol', 'Bhat'];
        $sgAreas = ['SG Highway', 'Sola', 'Thaltej', 'Gota', 'Pakwan Cross Road'];

        foreach ($westAreas as $i => $name) {
            $this->geo('area', $name, $west->id, $i);
        }
        foreach ($eastAreas as $i => $name) {
            $this->geo('area', $name, $east->id, $i);
        }
        foreach ($sgAreas as $i => $name) {
            $this->geo('area', $name, $sg->id, $i);
        }

        $roads = [
            ['SG Highway', $sg->id],
            ['Sindhu Bhavan Road', $west->id],
            ['VIP Road', $east->id],
            ['Sarkhej-Gandhinagar Highway', $sg->id],
            ['132 Feet Ring Road', $west->id],
            ['SP Ring Road', $city->id],
            ['Ashram Road', $west->id],
            ['Drive-In Road', $west->id],
        ];
        foreach ($roads as $i => [$name, $parent]) {
            $this->geo('road', $name, $parent, $i);
        }

        $landmarks = [
            ['Kankaria Lake', $east->id],
            ['Sabarmati Riverfront', $west->id],
            ['Science City', $west->id],
            ['Gift City', $east->id],
            ['Gujarat University', $west->id],
            ['Vaishno Devi Circle', $west->id],
            ['Pakwan Cross Road', $sg->id],
            ['ISRO', $west->id],
        ];
        foreach ($landmarks as $i => [$name, $parent]) {
            $this->geo('landmark', $name, $parent, $i);
        }

        $this->line('Geo nodes: '.AiGeoNode::count());
    }

    private function geo(string $type, string $name, ?int $parentId, int $sort): AiGeoNode
    {
        $slug = Str::slug($name);

        return AiGeoNode::query()->updateOrCreate(
            ['type' => $type, 'slug' => $slug],
            ['name' => $name, 'parent_id' => $parentId, 'status' => true, 'sort_order' => $sort]
        );
    }

    private function seedAngles(): void
    {
        $angles = [
            ['key' => 'area-buy-guide', 'name' => 'Area buy guide', 'audience' => 'Buyers', 'intent' => 'area', 'category_slug' => 'areas', 'title_pattern' => 'Is {area} a Good Place to Buy a Flat in Ahmedabad?', 'brief' => 'Pros, cons, who it suits, price bands, connectivity.'],
            ['key' => 'budget-2bhk', 'name' => 'Budget 2 BHK', 'audience' => 'Budget buyers', 'intent' => 'budget', 'category_slug' => 'buy', 'title_pattern' => 'Best 2 BHK Flats Under ₹75 Lakh in {area}, Ahmedabad', 'brief' => 'Budget options, what to check, hidden costs.'],
            ['key' => 'budget-under-1cr', 'name' => 'Under 1 crore', 'audience' => 'Budget buyers', 'intent' => 'budget', 'category_slug' => 'buy', 'title_pattern' => 'Best Flats Under 1 Crore in {area}: Options and Checks', 'brief' => 'Area-wise shortlist under 1 crore.'],
            ['key' => 'compare-areas', 'name' => 'Area comparison', 'audience' => 'Upgraders', 'intent' => 'compare', 'category_slug' => 'compare', 'title_pattern' => '{area} vs Nearby Localities: Where to Buy in Ahmedabad', 'brief' => 'Price, lifestyle, schools, commute compared.'],
            ['key' => 'near-road', 'name' => 'Near road', 'audience' => 'Commute buyers', 'intent' => 'guide', 'category_slug' => 'areas', 'title_pattern' => 'Best Projects to Look at on {road}, Ahmedabad', 'brief' => 'Connectivity-led shortlist along the road.'],
            ['key' => 'near-landmark', 'name' => 'Near landmark', 'audience' => 'Families', 'intent' => 'guide', 'category_slug' => 'areas', 'title_pattern' => 'Best Projects Near {landmark} in Ahmedabad', 'brief' => 'Lifestyle and location advantages near the landmark.'],
            ['key' => 'first-time', 'name' => 'First-time buyer', 'audience' => 'First-time buyers', 'intent' => 'process', 'category_slug' => 'buy', 'title_pattern' => 'First-Time Home Buyer Checklist for {area}, Ahmedabad', 'brief' => 'Documents, RERA, loan, negotiation checklist.'],
            ['key' => 'investor', 'name' => 'Investor angle', 'audience' => 'Investors', 'intent' => 'guide', 'category_slug' => 'investment', 'title_pattern' => 'Is Buying a Flat in {area} a Good Investment in 2026?', 'brief' => 'Rental demand, appreciation drivers, risks.'],
            ['key' => 'nri', 'name' => 'NRI guide', 'audience' => 'NRIs', 'intent' => 'process', 'category_slug' => 'nri', 'title_pattern' => 'NRI Guide to Buying a Flat in {area}, Ahmedabad', 'brief' => 'Documents, remote buying, tax and handover.'],
            ['key' => 'rent-area', 'name' => 'Rent area', 'audience' => 'Tenants', 'intent' => 'area', 'category_slug' => 'rent', 'title_pattern' => 'Flats for Rent in {area}, Ahmedabad: What You Actually Get', 'brief' => 'Rent bands, furnishing, commute, who it suits.'],
            ['key' => 'family-schools', 'name' => 'Families schools', 'audience' => 'Families', 'intent' => 'guide', 'category_slug' => 'areas', 'title_pattern' => 'Best Areas Near {area} for Families with School-Going Children', 'brief' => 'Schools, parks, safety, 3 BHK options.'],
            ['key' => 'ready-vs-uc', 'name' => 'Ready vs UC', 'audience' => 'Buyers', 'intent' => 'compare', 'category_slug' => 'buy', 'title_pattern' => 'Ready-to-Move vs Under-Construction Flats in {area}', 'brief' => 'Cost, risk, possession timeline trade-offs.'],
        ];

        foreach ($angles as $i => $row) {
            AiAngle::query()->updateOrCreate(
                ['key' => $row['key']],
                array_merge($row, ['status' => true, 'sort_order' => $i])
            );
        }
        $this->line('Angles: '.AiAngle::count());
    }

    private function seedTemplates(): void
    {
        $keywordRules = [
            'primary' => ['times' => '4-6', 'where' => 'Title, H1, first 100 words, one H2, FAQ, conclusion'],
            'secondary_lsi' => ['times' => '1-2 each, 6-8 different', 'where' => 'Headings and body, natural sentences'],
            'short_tail' => ['times' => '2-3', 'where' => 'Intro, one H2, meta description'],
            'long_tail' => ['times' => '1 each, 4-6 different', 'where' => 'H2/H3, FAQ questions'],
            'question' => ['times' => '5-6', 'where' => 'FAQ headings exactly as people ask'],
            'local_geo' => ['times' => '1-2 each, 3-4 different', 'where' => 'Location section, FAQ, meta'],
            'trust' => ['times' => '1 each', 'where' => 'RERA, developer and FAQ sections'],
            'rules' => [
                'Spread mentions; never stack 3 in one paragraph.',
                'Vary wording (project name / this project / the flats).',
                'Strongest keywords in title, H1, first paragraph, H2s, FAQ, meta.',
                'Read aloud; rewrite forced sentences.',
                'Do not reuse the same keyword set across blogs.',
            ],
        ];

        $guideSections = [
            'Introduction',
            'Area / topic overview',
            'Who this is for',
            'Location and nearby places',
            'Connectivity and accessibility',
            'Price and configuration reality check',
            'Things to know before buying or renting',
            'Frequently asked questions',
            'Practical next steps / CTA',
        ];

        $compareSections = [
            'Introduction and who should compare',
            'Quick comparison table',
            'Price and lifestyle',
            'Commute and connectivity',
            'Schools, amenities and daily life',
            'Which buyer profile fits which option',
            'Frequently asked questions',
            'Final recommendation framework',
        ];

        $areaSections = [
            'Introduction',
            'What {area} is known for',
            'Pros and cons',
            'Connectivity',
            'Price bands and property types',
            'Who should buy or rent here',
            'Things to verify locally',
            'Frequently asked questions',
            'Bottom line',
        ];

        $templates = [
            ['key' => 'guide', 'name' => 'Guide article', 'content_type' => 'guide', 'sections' => $guideSections],
            ['key' => 'compare', 'name' => 'Comparison article', 'content_type' => 'compare', 'sections' => $compareSections],
            ['key' => 'area', 'name' => 'Area hub article', 'content_type' => 'area', 'sections' => $areaSections],
        ];

        foreach ($templates as $tpl) {
            AiStructureTemplate::query()->updateOrCreate(
                ['key' => $tpl['key']],
                [
                    'name' => $tpl['name'],
                    'content_type' => $tpl['content_type'],
                    'sections' => $tpl['sections'],
                    'keyword_rules' => $keywordRules,
                    'notes' => 'Derived from Blog Ideas sheet keyword placement and section rules.',
                    'status' => true,
                ]
            );
        }
        $this->line('Structure templates: '.AiStructureTemplate::count());
    }

    private function seedEditors(): void
    {
        $editors = [
            ['name' => 'Ananya Mehta', 'bio' => 'Ahmedabad housing researcher focused on first-time buyers and west-side localities.'],
            ['name' => 'Rohan Patel', 'bio' => 'Writes practical buy/rent guides with an eye on commute and pricing reality.'],
            ['name' => 'Kavya Shah', 'bio' => 'Covers NRI purchases, documentation and long-distance decision making.'],
            ['name' => 'Harsh Trivedi', 'bio' => 'Investment and rental-demand notes for Ahmedabad micro-markets.'],
            ['name' => 'Isha Desai', 'bio' => 'Family-home specialist: schools, parks, 3 BHK trade-offs.'],
            ['name' => 'Manav Joshi', 'bio' => 'Compares corridors like SG Highway, SP Ring Road and east Ahmedabad growth.'],
            ['name' => 'Priya Chauhan', 'bio' => 'Budget-flat researcher for sub-1 crore and under-construction risks.'],
            ['name' => 'Devansh Raval', 'bio' => 'Legal and process explainers: RERA, stamp duty, registration basics.'],
            ['name' => 'Sneha Pandya', 'bio' => 'Renters’ guides — deposits, furnishing and area fit for professionals.'],
            ['name' => 'Aarav Solanki', 'bio' => 'Locality deep-dives with plain-language checklists for site visits.'],
        ];

        foreach ($editors as $row) {
            Author::query()->updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    'name' => $row['name'],
                    'bio' => $row['bio'],
                    'status' => true,
                ]
            );
        }
        $this->line('Editors/authors ready (10+).');
    }
}
