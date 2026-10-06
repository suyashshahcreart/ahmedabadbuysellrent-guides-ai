@php
$schemaData = json_encode([
    'id' => $schema->id,
    'schema_type' => $schema->schema_type,
    'schema_name' => $schema->schema_name ?? '',
    'schema_json' => is_string($schema->schema_json)
        ? (json_encode(json_decode($schema->schema_json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $schema->schema_json)
        : '',
    'is_auto_generated' => (bool) $schema->is_auto_generated,
    'sort_order' => (int) $schema->sort_order,
    'is_active' => (bool) $schema->is_active,
], JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp
<div class="seo-schema-item seo-schema-row p-3 mb-2 border rounded" data-id="{{ $schema->id }}"
     data-schema="{{ $schemaData }}">
    <div class="d-flex align-items-start justify-content-between gap-2">
        <div>
            <strong class="text-primary">{{ $schema->schema_type }}</strong>
            @if($schema->schema_name)
                <span class="text-muted small"> — {{ $schema->schema_name }}</span>
            @endif
            @if($schema->is_auto_generated)
                <span class="badge bg-soft-info text-info ms-1">Auto</span>
            @else
                <span class="badge bg-soft-secondary text-secondary ms-1">Manual</span>
            @endif
            @if($schema->is_active)
                <span class="badge bg-soft-success text-success ms-1">Active</span>
            @else
                <span class="badge bg-soft-secondary text-secondary ms-1">Inactive</span>
            @endif
        </div>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-soft-primary" onclick="openSchemaEdit({{ $schema->id }})">
                <i class="ri-pencil-line"></i>
            </button>
            <button type="button" class="btn btn-sm btn-soft-danger" onclick="deleteSchemaRow({{ $schema->id }})">
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>
    </div>
</div>
