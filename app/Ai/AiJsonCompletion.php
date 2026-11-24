<?php

namespace App\Ai;

/**
 * An answer of the model decoded as a JSON object, with what it cost and the ai_calls row of the call.
 * The data passed the caller's check, but stays untrusted: each value is checked again before it is used.
 */
final readonly class AiJsonCompletion
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public float $cost,
        public int $aiCallId,
    ) {}
}
