<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI provider
    |--------------------------------------------------------------------------
    |
    | The provider used when no admin setting ("ai.provider") is stored.
    | Values: see App\Ai\AiProvider (openrouter, fake, fake_timeout,
    | fake_outage, fake_credit, fake_invalid).
    |
    */

    'default_provider' => env('AI_PROVIDER', 'fake'),

    'model' => env('AI_MODEL', 'mistralai/mistral-small-3.2-24b-instruct'),

    // Seconds before a call to the provider is abandoned.
    'timeout' => (int) env('AI_TIMEOUT', 20),

    /*
    |--------------------------------------------------------------------------
    | Daily quota per tier (App\Enums\Tier)
    |--------------------------------------------------------------------------
    */

    'tiers' => [
        'free' => ['daily_calls' => 10],
        'pro' => ['daily_calls' => 100],
    ],

];
