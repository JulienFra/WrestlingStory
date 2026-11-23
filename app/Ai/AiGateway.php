<?php

namespace App\Ai;

use App\Models\Setting;
use App\Models\User;

/**
 * The only way the rest of the application talks to the language model.
 * In order: daily quota (before any call), current provider, timed call, check of the output, one ai_calls row.
 * Neither the prompt nor the answer is written to ai_calls.
 */
class AiGateway
{
    /** The error written to ai_calls when the quota refuses a call. Not counted in the quota. */
    public const QUOTA_ERROR = 'quota';

    /**
     * @throws QuotaExceededException when the player has no call left today
     * @throws AiException when the provider fails or its answer is unusable
     */
    public function complete(User $user, string $feature, string $system, string $prompt, int $maxTokens, int $maxChars): AiCompletion
    {
        $provider = $this->currentProvider();

        if ($this->remainingToday($user) === 0) {
            $this->record($user, $feature, $provider, $this->modelFor($provider), 0, self::QUOTA_ERROR);

            throw new QuotaExceededException($this->dailyLimit($user));
        }

        $start = hrtime(true);

        try {
            $completion = $this->clientFor($provider)->complete($system, $prompt, $maxTokens);
        } catch (AiException $exception) {
            $this->record($user, $feature, $provider, $this->modelFor($provider), $this->elapsedMs($start), $exception->kind->value);

            throw $exception;
        }

        $text = trim($completion->text);
        $usable = $text !== '' && mb_strlen($text) <= $maxChars;
        $error = $usable ? null : AiErrorKind::InvalidResponse->value;

        // The tokens are counted even when the answer is refused: they were paid for.
        $this->record($user, $feature, $provider, $completion->model, $this->elapsedMs($start), $error, $completion);

        if (! $usable) {
            throw new AiException(AiErrorKind::InvalidResponse);
        }

        return new AiCompletion($text, $completion->model, $completion->inputTokens, $completion->outputTokens, $completion->cost);
    }

    public function dailyLimit(User $user): int
    {
        return (int) config("ai.tiers.{$user->tier->value}.daily_calls", 0);
    }

    public function remainingToday(User $user): int
    {
        $usedToday = $user->aiCalls()
            ->where('created_at', '>=', now()->startOfDay())
            ->where(fn ($query) => $query->whereNull('error')->orWhere('error', '!=', self::QUOTA_ERROR))
            ->count();

        return max(0, $this->dailyLimit($user) - $usedToday);
    }

    /**
     * The admin setting "ai.provider", otherwise config('ai.default_provider'). An unknown value falls back to the config.
     */
    public function currentProvider(): AiProvider
    {
        $default = AiProvider::tryFrom((string) config('ai.default_provider')) ?? AiProvider::Fake;

        return AiProvider::tryFrom((string) Setting::get('ai.provider')) ?? $default;
    }

    private function clientFor(AiProvider $provider): AiClient
    {
        if ($provider === AiProvider::OpenRouter) {
            return app(OpenRouterClient::class);
        }

        return app()->make(FakeAiClient::class, ['provider' => $provider]);
    }

    private function modelFor(AiProvider $provider): string
    {
        return $provider === AiProvider::OpenRouter ? (string) config('ai.model') : 'fake';
    }

    private function elapsedMs(int $start): int
    {
        return intdiv(hrtime(true) - $start, 1_000_000);
    }

    private function record(User $user, string $feature, AiProvider $provider, string $model, int $durationMs, ?string $error, ?AiCompletion $completion = null): void
    {
        $user->aiCalls()->create([
            'feature' => $feature,
            'provider' => $provider === AiProvider::OpenRouter ? 'openrouter' : 'fake',
            'model' => $model,
            'input_tokens' => $completion !== null ? $completion->inputTokens : 0,
            'output_tokens' => $completion !== null ? $completion->outputTokens : 0,
            'cost' => $completion !== null ? $completion->cost : 0,
            'duration_ms' => $durationMs,
            'success' => $error === null,
            'error' => $error,
        ]);
    }
}
