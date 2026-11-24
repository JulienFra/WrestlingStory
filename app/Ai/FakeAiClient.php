<?php

namespace App\Ai;

use Illuminate\Support\Str;
use stdClass;

/**
 * A provider without network, for local work, tests and demos.
 * "fake" answers a fictional promo, or with a JSON Schema a JSON built from that schema;
 * the other values throw the matching AI error.
 */
class FakeAiClient implements AiClient
{
    /** The text of every string of a JSON answer, cut to the maxLength of the schema. */
    private const FAKE_TEXT = 'Un récit inventé par le faux fournisseur, sans appel au modèle.';

    public function __construct(private AiProvider $provider) {}

    public function complete(string $system, string $prompt, int $maxTokens, ?AiJsonSchema $jsonSchema = null): AiCompletion
    {
        $kind = match ($this->provider) {
            AiProvider::FakeTimeout => AiErrorKind::Timeout,
            AiProvider::FakeOutage => AiErrorKind::ProviderDown,
            AiProvider::FakeCredit => AiErrorKind::CreditExhausted,
            AiProvider::FakeInvalid => AiErrorKind::InvalidResponse,
            default => null,
        };

        if ($kind !== null) {
            throw new AiException($kind);
        }

        $text = $jsonSchema === null
            ? $this->promo($prompt)
            : (string) json_encode($this->valueFor($jsonSchema->schema), JSON_UNESCAPED_UNICODE);

        return new AiCompletion(
            text: $text,
            model: 'fake',
            inputTokens: str_word_count($system.' '.$prompt),
            outputTokens: str_word_count($text),
            cost: 0.0,
        );
    }

    private function promo(string $prompt): string
    {
        $subject = Str::limit(trim($prompt), 80);

        return "Écoutez-moi bien, tout le vestiaire ! On m'a décrit comme ça : « {$subject} ». "
            .'Ce soir, sous les projecteurs, je prends le micro et je prends la ceinture. '
            .'Et lundi prochain, toute la fédération saura mon nom !';
    }

    /**
     * A deterministic value that follows the schema: the first "enum" value when there is one,
     * otherwise every property of an object, one item in an array, the "minimum" (or 0) for a number,
     * true for a boolean and a short French text for a string. In a union with "null", the other type wins.
     *
     * @param  array<array-key, mixed>  $schema
     */
    private function valueFor(array $schema): mixed
    {
        if (isset($schema['enum']) && is_array($schema['enum']) && $schema['enum'] !== []) {
            return reset($schema['enum']);
        }

        $type = $schema['type'] ?? 'string';

        if (is_array($type)) {
            $type = collect($type)->first(fn ($candidate) => $candidate !== 'null') ?? 'null';
        }

        return match ($type) {
            'object' => $this->objectFor($schema),
            'array' => [$this->valueFor(is_array($schema['items'] ?? null) ? $schema['items'] : [])],
            'integer', 'number' => $schema['minimum'] ?? 0,
            'boolean' => true,
            'null' => null,
            default => $this->textFor($schema),
        };
    }

    /**
     * An object, so that an object without properties is still encoded as {} and not [].
     *
     * @param  array<array-key, mixed>  $schema
     */
    private function objectFor(array $schema): stdClass
    {
        $object = new stdClass;
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];

        foreach ($properties as $name => $property) {
            $object->{$name} = $this->valueFor(is_array($property) ? $property : []);
        }

        return $object;
    }

    /**
     * @param  array<array-key, mixed>  $schema
     */
    private function textFor(array $schema): string
    {
        $maxLength = $schema['maxLength'] ?? null;

        return is_int($maxLength) ? mb_substr(self::FAKE_TEXT, 0, $maxLength) : self::FAKE_TEXT;
    }
}
