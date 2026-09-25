<?php

namespace App\Repositories;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class OpenRouterRepository
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function complete(array $messages): string
    {
        $key = trim((string) config('services.openrouter.key'));

        if ($key === '') {
            throw new ServiceUnavailableHttpException(null, 'Assistente temporariamente indisponível.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($key)
                ->connectTimeout(10)
                ->timeout(60)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => config('services.openrouter.model'),
                    'messages' => $messages,
                    'temperature' => 0.2,
                    'max_tokens' => 8192,
                ]);
        } catch (ConnectionException) {
            throw new ServiceUnavailableHttpException(null, 'O assistente demorou para responder. Tente novamente.');
        }

        if ($response->failed()) {
            throw new ServiceUnavailableHttpException(null, 'Assistente temporariamente indisponível. Tente novamente.');
        }

        $reply = $response->json('choices.0.message.content');

        if (! is_string($reply) || trim($reply) === '') {
            throw new HttpException(502, 'O assistente não retornou uma resposta. Tente novamente.');
        }

        return trim($reply);
    }
}
