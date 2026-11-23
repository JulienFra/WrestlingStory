<?php

namespace App\Ai;

use Illuminate\Support\Str;

/**
 * A provider without network, for local work, tests and demos.
 * "fake" answers a fictional promo; the other values throw the matching AI error.
 */
class FakeAiClient implements AiClient
{
    public function __construct(private AiProvider $provider) {}

    public function complete(string $system, string $prompt, int $maxTokens): AiCompletion
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

        $subject = Str::limit(trim($prompt), 80);
        $text = "Écoutez-moi bien, tout le vestiaire ! On m'a décrit comme ça : « {$subject} ». "
            .'Ce soir, sous les projecteurs, je prends le micro et je prends la ceinture. '
            .'Et lundi prochain, toute la fédération saura mon nom !';

        return new AiCompletion(
            text: $text,
            model: 'fake',
            inputTokens: str_word_count($system.' '.$prompt),
            outputTokens: str_word_count($text),
            cost: 0.0,
        );
    }
}
