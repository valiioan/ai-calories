<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenAiClient
{
    /**
     * @param  list<array<string, mixed>>  $input
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    public function respond(string $instructions, array $input, array $tools, ?string $previousResponseId = null): array
    {
        $key = (string) config('services.openai.key');
        if ($key === '') {
            throw new RuntimeException('The assistant is not configured.');
        }

        $body = [
            'model' => (string) config('services.openai.model'),
            'instructions' => $instructions,
            'input' => $input,
            'tools' => $tools,
            'parallel_tool_calls' => true,
        ];
        $effort = (string) config('services.openai.reasoning_effort');
        if ($effort !== '') {
            $body['reasoning'] = ['effort' => $effort];
        }
        if ($previousResponseId) {
            $body['previous_response_id'] = $previousResponseId;
        }

        try {
            $response = Http::timeout(45)
                ->withToken($key)
                ->acceptJson()
                ->post(rtrim((string) config('services.openai.base_url'), '/').'/responses', $body);
        } catch (Throwable) {
            throw new RuntimeException('The assistant is unavailable.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('The assistant is unavailable.');
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
