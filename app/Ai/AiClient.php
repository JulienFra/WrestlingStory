<?php

namespace App\Ai;

/**
 * Something that can ask a language model. Only AiGateway uses it: it adds the quota and the log.
 */
interface AiClient
{
    /**
     * @throws AiException
     */
    public function complete(string $system, string $prompt, int $maxTokens): AiCompletion;
}
