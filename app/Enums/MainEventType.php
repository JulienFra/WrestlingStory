<?php

namespace App\Enums;

/**
 * Simple: the player picks two wrestlers. Rivalry: the wrestlers and the title come from an active rivalry (D-004).
 */
enum MainEventType: string
{
    case Simple = 'simple';
    case Rivalry = 'rivalry';
}
