<?php

namespace App\Ai;

/**
 * The four AI errors a player can meet. Each one has its own message, without technical detail.
 */
enum AiErrorKind: string
{
    case Timeout = 'timeout';
    case ProviderDown = 'provider_down';
    case CreditExhausted = 'credit_exhausted';
    case InvalidResponse = 'invalid_response';

    public function message(): string
    {
        return match ($this) {
            self::Timeout => 'Le modèle met trop de temps à répondre. Réessaie dans un instant.',
            self::ProviderDown => 'Le service d\'IA est indisponible pour le moment. Le reste du jeu fonctionne normalement.',
            self::CreditExhausted => 'Le crédit d\'IA du jeu est épuisé. Préviens un administrateur.',
            self::InvalidResponse => 'La réponse de l\'IA était inutilisable. Réessaie ou reformule ta demande.',
        };
    }
}
