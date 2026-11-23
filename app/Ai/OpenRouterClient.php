<?php

namespace App\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The real provider. The key stays on the server; only the system text and the player's text are sent
 * (never their e-mail, name or id), to providers that keep no data (zdr) and do not train on it.
 */
class OpenRouterClient implements AiClient
{
    public function complete(string $system, string $prompt, int $maxTokens): AiCompletion
    {
        $key = config('services.openrouter.key');

        if (! is_string($key) || $key === '') {
            Log::warning('OpenRouter call skipped: no API key configured.');

            throw new AiException(AiErrorKind::ProviderDown);
        }

        $model = (string) config('ai.model');
        $response = $this->send($key, $model, $system, $prompt, $maxTokens);

        if ($response->failed()) {
            Log::warning('OpenRouter call failed.', ['status' => $response->status()]);

            throw new AiException($this->kindForStatus($response->status()));
        }

        return $this->toCompletion($response, $model);
    }

    /**
     * @throws AiException
     */
    private function send(string $key, string $model, string $system, string $prompt, int $maxTokens): Response
    {
        try {
            return Http::withToken($key)
                ->acceptJson()
                ->timeout((int) config('ai.timeout'))
                ->post(rtrim((string) config('services.openrouter.url'), '/').'/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $maxTokens,
                    'provider' => ['zdr' => true, 'data_collection' => 'deny'],
                    'usage' => ['include' => true],
                ]);
        } catch (ConnectionException $exception) {
            $timedOut = str_contains($exception->getMessage(), 'cURL error 28')
                || str_contains(strtolower($exception->getMessage()), 'timed out');

            Log::warning('OpenRouter connection failed.', ['timed_out' => $timedOut]);

            throw new AiException($timedOut ? AiErrorKind::Timeout : AiErrorKind::ProviderDown, $exception);
        }
    }

    private function kindForStatus(int $status): AiErrorKind
    {
        return match ($status) {
            402 => AiErrorKind::CreditExhausted,
            408 => AiErrorKind::Timeout,
            default => AiErrorKind::ProviderDown,
        };
    }

    /**
     * @throws AiException
     */
    private function toCompletion(Response $response, string $requestedModel): AiCompletion
    {
        $text = $response->json('choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            Log::warning('OpenRouter answered without usable text.');

            throw new AiException(AiErrorKind::InvalidResponse);
        }

        $model = $response->json('model');

        return new AiCompletion(
            text: $text,
            model: is_string($model) && $model !== '' ? $model : $requestedModel,
            inputTokens: (int) $response->json('usage.prompt_tokens', 0),
            outputTokens: (int) $response->json('usage.completion_tokens', 0),
            cost: (float) $response->json('usage.cost', 0),
        );
    }
}
