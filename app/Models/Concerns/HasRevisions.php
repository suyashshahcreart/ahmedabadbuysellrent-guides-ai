<?php

namespace App\Models\Concerns;

use App\Models\Revision;

trait HasRevisions
{
    public function revisions()
    {
        return $this->morphMany(Revision::class, 'revisable')->latest();
    }

    public function recordRevision(?int $userId = null): void
    {
        $this->revisions()->create([
            'payload' => $this->toArray(),
            'user_id' => $userId,
        ]);
    }
}
