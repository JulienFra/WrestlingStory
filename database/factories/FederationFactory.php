<?php

namespace Database\Factories;

use App\Models\Federation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Federation>
 */
class FederationFactory extends Factory
{
    /**
     * A fictional federation with an invented name.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Fédération '.fake()->unique()->lastName(),
            'description' => fake()->sentence(),
        ];
    }
}
