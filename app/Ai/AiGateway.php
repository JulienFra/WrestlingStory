<?php

namespace App\Ai;

use App\Models\AiCall;
use App\Models\Setting;
use App\Models\User;
use Closure;

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
        [$provider, $completion, $durationMs] = $this->ask(
            $user,
            $feature,
            fn (AiClient $client) => $client->complete($system, $prompt, $maxTokens),
        );

        $text = trim($completion->text);
        $usable = $text !== '' && mb_strlen($text) <= $maxChars;
        $error = $usable ? null : AiErrorKind::InvalidResponse->value;

        // The tokens are counted even when the answer is refused: they were paid for.
        $this->record($user, $feature, $provider, $completion->model, $durationMs, $error, $completion);

        if (! $usable) {
            throw new AiException(AiErrorKind::InvalidResponse);
        }

        return new AiCompletion($text, $completion->model, $completion->inputTokens, $completion->outputTokens, $completion->cost);
    }

    /**
     * Same as complete(), but the model must answer a JSON object that follows $schema,
     * and that object must pass $isUsable (the caller's domain check). Otherwise: InvalidResponse.
     *
     * @param  array<string, mixed>  $schema  JSON Schema of the expected object
     * @param  Closure(array<string, mixed>): bool  $isUsable  domain check of the decoded object
     *
     * @throws QuotaExceededException when the player has no call left today
     * @throws AiException when the provider fails or its answer is unusable
     */
    public function completeJson(User $user, string $feature, string $system, string $prompt, int $maxTokens, string $schemaName, array $schema, Closure $isUsable): AiJsonCompletion
    {
        $jsonSchema = new AiJsonSchema($schemaName, $schema);

        [$provider, $completion, $durationMs] = $this->ask(
            $user,
            $feature,
            fn (AiClient $client) => $client->complete($system, $prompt, $maxTokens, $jsonSchema),
        );

        $data = $this->decodeObject($completion->text);
        $usable = $data !== null && $isUsable($data) === true;
        $error = $usable ? null : AiErrorKind::InvalidResponse->value;

        // The tokens are counted even when the answer is refused: they were paid for.
        $call = $this->record($user, $feature, $provider, $completion->model, $durationMs, $error, $completion);

        if ($data === null || ! $usable) {
            throw new AiException(AiErrorKind::InvalidResponse);
        }

        return new AiJsonCompletion($data, $completion->model, $completion->inputTokens, $completion->outputTokens, $completion->cost, $call->id);
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

    /**
     * The common start of every call: daily quota (a refusal is logged), then the timed call with the current client.
     * A provider error is logged and thrown again; the answer itself is checked and logged by the caller.
     *
     * @param  Closure(AiClient): AiCompletion  $call
     * @return array{AiProvider, AiCompletion, int} the provider, the raw answer and the duration in milliseconds
     *
     * @throws QuotaExceededException
     * @throws AiException
     */
    private function ask(User $user, string $feature, Closure $call): array
    {
        $provider = $this->currentProvider();

        if ($this->remainingToday($user) === 0) {
            $this->record($user, $feature, $provider, $this->modelFor($provider), 0, self::QUOTA_ERROR);

            throw new QuotaExceededException($this->dailyLimit($user));
        }

        $start = hrtime(true);

        try {
            $completion = $call($this->clientFor($provider));
        } catch (AiException $exception) {
            $this->record($user, $feature, $provider, $this->modelFor($provider), $this->elapsedMs($start), $exception->kind->value);

            throw $exception;
        }

        return [$provider, $completion, $this->elapsedMs($start)];
    }

    /**
     * The answer as a JSON object, or null when it is not valid JSON or not an object (a list, a string, a number).
     *
     * @return array<string, mixed>|null
     */
    private function decodeObject(string $text): ?array
    {
        $data = json_decode(trim($text), true);

        if (! is_array($data) || $data === [] || array_is_list($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        return $data;
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

    private function record(User $user, string $feature, AiProvider $provider, string $model, int $durationMs, ?string $error, ?AiCompletion $completion = null): AiCall
    {
        return $user->aiCalls()->create([
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
