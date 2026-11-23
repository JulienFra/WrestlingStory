<?php

namespace Database\Factories;

use App\Models\AiCall;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiCall>
 */
class AiCallFactory extends Factory
{
    /**
     * A successful call to the fake provider.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'feature' => 'promo',
            'provider' => 'fake',
            'model' => 'fake',
            'input_tokens' => 50,
            'output_tokens' => 120,
            'cost' => 0,
            'duration_ms' => 5,
            'success' => true,
            'error' => null,
        ];
    }

    /**
     * A call refused by the daily quota.
     */
    public function quotaRefused(): static
    {
        return $this->state(fn (array $attributes) => [
            'input_tokens' => 0,
            'output_tokens' => 0,
            'duration_ms' => 0,
            'success' => false,
            'error' => 'quota',
        ]);
    }
}
