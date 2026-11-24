<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Limits per tier (App\Enums\Tier)
    |--------------------------------------------------------------------------
    |
    | D-004: a player may run 1 federation on the free tier, 10 on the pro tier.
    | The server checks the limit on creation (App\Policies\FederationPolicy).
    | A player back on free keeps their federations but cannot create more.
    |
    */

    'tiers' => [
        'free' => ['max_federations' => 1],
        'pro' => ['max_federations' => 10],
    ],

];
