<?php

namespace App\Enums;

/**
 * Scheduled: can still be edited or deleted. Played: frozen, with its winner and story (D-004).
 */
enum MainEventStatus: string
{
    case Scheduled = 'scheduled';
    case Played = 'played';
}
