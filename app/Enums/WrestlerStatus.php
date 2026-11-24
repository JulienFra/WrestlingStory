<?php

namespace App\Enums;

/**
 * Whether a wrestler can be booked: an injured wrestler cannot.
 */
enum WrestlerStatus: string
{
    case Active = 'active';
    case Injured = 'injured';
}
