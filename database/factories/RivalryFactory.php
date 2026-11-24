<?php

namespace Database\Factories;

use App\Enums\RivalryStatus;
use App\Models\Federation;
use App\Models\Rivalry;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rivalry>
 */
class RivalryFactory extends Factory
{
    /**
     * An active rivalry between two new wrestlers of the rivalry's federation.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'federation_id' => Federation::factory(),
            'wrestler_a_id' => fn (array $attributes) => Wrestler::factory()->state(['federation_id' => $attributes['federation_id']]),
            'wrestler_b_id' => fn (array $attributes) => Wrestler::factory()->state(['federation_id' => $attributes['federation_id']]),
            'intensity' => fake()->numberBetween(0, 100),
            'status' => RivalryStatus::Active,
        ];
    }

    /**
     * Indicate that the rivalry is over.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RivalryStatus::Ended,
        ]);
    }
}
