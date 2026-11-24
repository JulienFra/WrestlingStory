<?php

namespace Database\Factories;

use App\Enums\MainEventStatus;
use App\Enums\MainEventType;
use App\Models\Federation;
use App\Models\MainEvent;
use App\Models\Rivalry;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MainEvent>
 */
class MainEventFactory extends Factory
{
    /**
     * A scheduled simple match between two new wrestlers of the main event's federation, next week.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'federation_id' => Federation::factory(),
            'type' => MainEventType::Simple,
            'rivalry_id' => null,
            'wrestler_a_id' => fn (array $attributes) => Wrestler::factory()->state(['federation_id' => $attributes['federation_id']]),
            'wrestler_b_id' => fn (array $attributes) => Wrestler::factory()->state(['federation_id' => $attributes['federation_id']]),
            'title_id' => null,
            'scheduled_on' => now()->addWeek()->toDateString(),
            'imposed_winner_id' => null,
            'status' => MainEventStatus::Scheduled,
        ];
    }

    /**
     * A rivalry match: the wrestlers and the title come from the rivalry.
     */
    public function forRivalry(Rivalry $rivalry): static
    {
        return $this->state(fn (array $attributes) => [
            'federation_id' => $rivalry->federation_id,
            'type' => MainEventType::Rivalry,
            'rivalry_id' => $rivalry->id,
            'wrestler_a_id' => $rivalry->wrestler_a_id,
            'wrestler_b_id' => $rivalry->wrestler_b_id,
            'title_id' => $rivalry->title_id,
        ]);
    }

    /**
     * A match already played: wrestler A won, with a short story.
     */
    public function played(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MainEventStatus::Played,
            'scheduled_on' => now()->subWeek()->toDateString(),
            // A closure, so it reads the id of wrestler A once that wrestler is created.
            'winner_id' => fn (array $attributes) => $attributes['wrestler_a_id'],
            'story' => 'Un match serré, conclu par un tombé au bout de vingt minutes.',
            'follow_up' => 'Le perdant réclame une revanche.',
            'played_at' => now()->subWeek(),
        ]);
    }
}
