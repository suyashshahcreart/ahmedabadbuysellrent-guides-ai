<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Article;
use App\Models\SeoSchema;
use App\Services\SchemaSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ManagesArticleSchemas
{
    public function storeSeoSchema(Request $request, Article $article, SchemaSync $sync): JsonResponse
    {
        $data = $this->validatedSchema($request);
        $schema = $article->seoSchemas()->create([
            'schema_type' => $data['schema_type'],
            'schema_name' => $data['schema_name'] ?? $data['schema_type'],
            'schema_json' => $data['schema_json'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'is_auto_generated' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schema saved.',
            'schema' => $sync->formatSchema($schema),
            'data' => $sync->formatSchema($schema),
        ]);
    }

    public function updateSeoSchema(Request $request, Article $article, SeoSchema $seoSchema, SchemaSync $sync): JsonResponse
    {
        $this->assertArticleSchema($article, $seoSchema);
        $data = $this->validatedSchema($request);
        $seoSchema->update([
            'schema_type' => $data['schema_type'],
            'schema_name' => $data['schema_name'] ?? $data['schema_type'],
            'schema_json' => $data['schema_json'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'is_auto_generated' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schema updated.',
            'schema' => $sync->formatSchema($seoSchema->fresh()),
            'data' => $sync->formatSchema($seoSchema->fresh()),
        ]);
    }

    public function deleteSeoSchema(Article $article, SeoSchema $seoSchema): JsonResponse
    {
        $this->assertArticleSchema($article, $seoSchema);
        $seoSchema->delete();

        return response()->json(['success' => true, 'message' => 'Schema deleted.']);
    }

    public function generateSeoSchema(Request $request, Article $article, SchemaSync $sync): JsonResponse
    {
        $type = $request->validate([
            'schema_type' => 'required|string|max:100|in:'.implode(',', SchemaSync::supportedTypes()),
        ])['schema_type'];

        try {
            $schema = $sync->generateOne($article, $type);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Schema generated from guide content.',
            'schema' => $sync->formatSchema($schema),
            'data' => $sync->formatSchema($schema),
        ]);
    }

    public function generateAllSeoSchemas(Request $request, Article $article, SchemaSync $sync): JsonResponse
    {
        $replace = (bool) ($request->validate(['replace' => 'nullable|boolean'])['replace'] ?? false);

        try {
            $result = $sync->generateAll($article, $replace);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $created = count($result['generated']);
        $updated = count($result['updated']);
        $skipped = $result['skipped'];

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Generated %d schema block%s (%d updated%s).',
                $created,
                $created === 1 ? '' : 's',
                $updated,
                $skipped !== [] ? '; skipped: '.implode(', ', $skipped) : ''
            ),
            'schemas' => $result['schemas'],
            'skipped' => $skipped,
            'generated_count' => $created,
            'updated_count' => $updated,
        ]);
    }

    private function validatedSchema(Request $request): array
    {
        $data = $request->validate([
            'schema_type' => 'required|string|max:100',
            'schema_name' => 'nullable|string|max:255',
            'schema_json' => 'required|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        json_decode($data['schema_json']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'schema_json' => ['Invalid JSON: '.json_last_error_msg()],
            ]);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function assertArticleSchema(Article $article, SeoSchema $seoSchema): void
    {
        abort_unless(
            (int) $seoSchema->schemaable_id === (int) $article->id
            && $seoSchema->schemaable_type === $article->getMorphClass(),
            404
        );
    }
}
