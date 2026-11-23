<?php

namespace App\Ai;

use RuntimeException;

/**
 * The player has used all the AI calls of their tier for today. Thrown before any call to the model.
 */
class QuotaExceededException extends RuntimeException
{
    public function __construct(public readonly int $dailyLimit)
    {
        parent::__construct(
            "Tu as utilisé tes {$dailyLimit} générations d'IA d'aujourd'hui. Reviens demain ou demande un palier supérieur."
        );
    }
}
