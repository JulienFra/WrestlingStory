<?php

namespace Database\Factories;

use App\Enums\Alignment;
use App\Enums\WrestlerStatus;
use App\Models\Federation;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wrestler>
 */
class WrestlerFactory extends Factory
{
    /**
     * An active wrestler with an invented ring name and stats between 0 and 100.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'federation_id' => Federation::factory(),
            'ring_name' => fake()->unique()->firstName().' '.fake()->lastName(),
            'gimmick' => fake()->sentence(),
            'alignment' => fake()->randomElement(Alignment::cases()),
            'power' => fake()->numberBetween(0, 100),
            'technique' => fake()->numberBetween(0, 100),
            'charisma' => fake()->numberBetween(0, 100),
            'stamina' => fake()->numberBetween(0, 100),
            'popularity' => fake()->numberBetween(0, 100),
            'status' => WrestlerStatus::Active,
        ];
    }

    /**
     * Indicate that the wrestler is injured.
     */
    public function injured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WrestlerStatus::Injured,
        ]);
    }
}
