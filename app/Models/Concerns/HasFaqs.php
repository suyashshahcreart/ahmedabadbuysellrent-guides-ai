<?php

namespace App\Models\Concerns;

use App\Models\Faq;

trait HasFaqs
{
    public function faqs()
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }

    public function publishedFaqs()
    {
        return $this->faqs()->where('status', true);
    }
}
