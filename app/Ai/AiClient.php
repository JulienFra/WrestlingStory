<?php

namespace App\Ai;

/**
 * Something that can ask a language model. Only AiGateway uses it: it adds the quota and the log.
 */
interface AiClient
{
    /**
     * With a JSON Schema, the answer must be a JSON object that follows it. Without one, plain text.
     *
     * @throws AiException
     */
    public function complete(string $system, string $prompt, int $maxTokens, ?AiJsonSchema $jsonSchema = null): AiCompletion;
}
