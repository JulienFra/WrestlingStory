<?php

namespace App\Ai;

use RuntimeException;
use Throwable;

/**
 * A failed AI call. Its message is the French text shown to the player.
 */
class AiException extends RuntimeException
{
    public function __construct(public readonly AiErrorKind $kind, ?Throwable $previous = null)
    {
        parent::__construct($kind->message(), 0, $previous);
    }
}
