<?php

namespace App\Ai;

/**
 * The answer of the model, with what it cost. The text is still untrusted input.
 */
final readonly class AiCompletion
{
    public function __construct(
        public string $text,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public float $cost,
    ) {}
}
