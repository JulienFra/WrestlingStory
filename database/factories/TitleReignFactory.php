<?php

namespace Database\Factories;

use App\Models\Title;
use App\Models\TitleReign;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitleReign>
 */
class TitleReignFactory extends Factory
{
    /**
     * An open reign (current champion), held by a wrestler of the title's federation.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_id' => Title::factory(),
            'wrestler_id' => fn (array $attributes) => Wrestler::factory()->state([
                'federation_id' => Title::query()->whereKey($attributes['title_id'])->firstOrFail()->federation_id,
            ]),
            'started_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
            'ended_at' => null,
        ];
    }

    /**
     * Indicate that the reign is over (a former champion).
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
        ]);
    }
}
