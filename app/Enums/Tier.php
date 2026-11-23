<?php

namespace App\Enums;

/**
 * The plan of a player. The daily AI quota of each tier comes with the AI service (lot 2).
 */
enum Tier: string
{
    case Free = 'free';
    case Pro = 'pro';
}
