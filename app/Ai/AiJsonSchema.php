<?php

namespace App\Ai;

/**
 * The JSON Schema the answer of the model must follow, with the short name the provider asks for.
 */
final readonly class AiJsonSchema
{
    /**
     * @param  array<string, mixed>  $schema
     */
    public function __construct(
        public string $name,
        public array $schema,
    ) {}
}
