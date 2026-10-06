<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Services\SeoResolver;

class HubController extends Controller
{
    public function __invoke(string $slug, SeoResolver $seo, PageController $pages, CategoryController $categories)
    {
        if (Page::published()->where('slug', $slug)->exists()) {
            return $pages->show($slug, $seo);
        }

        if (Category::query()->where('status', 'published')->where('slug', $slug)->exists()) {
            return $categories->show($slug, $seo);
        }

        abort(404);
    }
}
