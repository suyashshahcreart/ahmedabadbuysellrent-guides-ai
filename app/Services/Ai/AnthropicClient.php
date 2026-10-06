<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicClient
{
    /**
     * @return array{text:string,input_tokens:int,output_tokens:int,model:string,raw:array}
     */
    public function messages(string $system, string $user, ?string $model = null, ?int $maxTokens = null): array
    {
        $key = (string) config('services.anthropic.key');
        if ($key === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $model = $model ?: (string) config('services.anthropic.model');
        $maxTokens = $maxTokens ?: (int) config('services.anthropic.max_tokens', 8000);
        $base = rtrim((string) config('services.anthropic.base_url', 'https://api.anthropic.com/v1'), '/');

        try {
            $response = Http::timeout(180)
                ->withHeaders([
                    'x-api-key' => $key,
                    'anthropic-version' => (string) config('services.anthropic.version', '2023-06-01'),
                    'content-type' => 'application/json',
                ])
                ->post($base.'/messages', [
                    'model' => $model,
                    'max_tokens' => $maxTokens,
                    'system' => $system,
                    'messages' => [
                        ['role' => 'user', 'content' => $user],
                    ],
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $body = $e->response?->json();
            $msg = is_array($body) ? ($body['error']['message'] ?? $e->getMessage()) : $e->getMessage();
            throw new RuntimeException('Anthropic API error: '.$msg, 0, $e);
        }

        $text = '';
        foreach (($response['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        return [
            'text' => trim($text),
            'input_tokens' => (int) data_get($response, 'usage.input_tokens', 0),
            'output_tokens' => (int) data_get($response, 'usage.output_tokens', 0),
            'model' => (string) ($response['model'] ?? $model),
            'raw' => $response,
        ];
    }
}
