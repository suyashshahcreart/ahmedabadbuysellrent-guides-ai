<?php

namespace App\Services\Ai;

use App\Models\AiAngle;
use App\Models\AiStructureTemplate;

class StructureResolver
{
    public function forAngle(AiAngle $angle): AiStructureTemplate
    {
        $key = match ($angle->intent) {
            'compare' => 'compare',
            'area' => 'area',
            default => 'guide',
        };

        $tpl = AiStructureTemplate::query()->active()->where('key', $key)->first();
        if ($tpl) {
            return $tpl;
        }

        return AiStructureTemplate::query()->active()->orderBy('id')->firstOrFail();
    }

    public function promptBlock(AiStructureTemplate $template): string
    {
        $sections = collect($template->sections ?? [])->values()->all();
        $rules = $template->keyword_rules ?? [];

        $lines = ["Content structure template: {$template->name} ({$template->key})"];
        $lines[] = 'Required H2 sections in order:';
        foreach ($sections as $i => $section) {
            $lines[] = ($i + 1).'. '.$section;
        }
        $lines[] = 'Keyword placement rules (JSON):';
        $lines[] = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return implode("\n", $lines);
    }
}
