<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSchema extends Model
{
    protected $fillable = [
        'schemaable_type', 'schemaable_id', 'schema_type', 'schema_name',
        'schema_json', 'is_auto_generated', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_auto_generated' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function schemaable()
    {
        return $this->morphTo();
    }

    public function decoded(): ?array
    {
        $data = json_decode((string) $this->schema_json, true);

        return is_array($data) ? $data : null;
    }

    public static function supportedTypes(): array
    {
        return \App\Services\SchemaSync::supportedTypes();
    }
}
