<?php

namespace Database\Factories;

use App\Models\Federation;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Title>
 */
class TitleFactory extends Factory
{
    /**
     * A vacant title with an invented name.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'federation_id' => Federation::factory(),
            'name' => 'Championnat '.fake()->unique()->city(),
            'prestige' => fake()->numberBetween(0, 100),
        ];
    }
}
