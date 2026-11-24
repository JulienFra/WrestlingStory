<?php

namespace Tests\Feature\Domain;

use App\Models\Rivalry;
use App\Models\Title;
use App\Models\TitleReign;
use App\Models\Wrestler;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The game rules the database enforces on its own, even if the code forgets them.
 */
class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_accepts_stats_between_0_and_100()
    {
        $wrestler = Wrestler::factory()->create(['power' => 0, 'popularity' => 100]);

        $this->assertModelExists($wrestler);
    }

    public function test_database_rejects_stat_above_100()
    {
        $this->expectException(QueryException::class);

        Wrestler::factory()->create(['power' => 101]);
    }

    public function test_database_rejects_rivalry_against_oneself()
    {
        $wrestler = Wrestler::factory()->create();

        $this->expectException(QueryException::class);

        Rivalry::factory()->create([
            'federation_id' => $wrestler->federation_id,
            'wrestler_a_id' => $wrestler->id,
            'wrestler_b_id' => $wrestler->id,
        ]);
    }

    public function test_title_cannot_have_two_open_reigns()
    {
        $title = Title::factory()->create();
        TitleReign::factory()->create(['title_id' => $title->id]);

        $this->expectException(QueryException::class);

        TitleReign::factory()->create(['title_id' => $title->id]);
    }

    public function test_title_can_have_an_ended_reign_and_an_open_one()
    {
        $title = Title::factory()->create();
        TitleReign::factory()->ended()->create(['title_id' => $title->id]);
        TitleReign::factory()->create(['title_id' => $title->id]);

        $this->assertSame(2, $title->reigns()->count());
    }

    public function test_database_rejects_reign_ending_before_it_starts()
    {
        $this->expectException(QueryException::class);

        TitleReign::factory()->create([
            'started_at' => now(),
            'ended_at' => now()->subDay(),
        ]);
    }
}
