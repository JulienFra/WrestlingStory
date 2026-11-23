<?php

namespace App\Ai;

/**
 * Who answers the AI calls. Chosen by an admin (setting "ai.provider"), otherwise config('ai.default_provider').
 * The Fake* values never touch the network: they let an admin trigger each of the four AI errors on purpose.
 */
enum AiProvider: string
{
    case OpenRouter = 'openrouter';
    case Fake = 'fake';
    case FakeTimeout = 'fake_timeout';
    case FakeOutage = 'fake_outage';
    case FakeCredit = 'fake_credit';
    case FakeInvalid = 'fake_invalid';

    public function label(): string
    {
        return match ($this) {
            self::OpenRouter => 'OpenRouter (réel)',
            self::Fake => 'Faux : réponse normale',
            self::FakeTimeout => 'Faux : délai dépassé',
            self::FakeOutage => 'Faux : fournisseur en panne',
            self::FakeCredit => 'Faux : crédit épuisé',
            self::FakeInvalid => 'Faux : réponse inexploitable',
        };
    }
}
