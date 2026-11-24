<?php

namespace Tests\Feature\Domain;

use App\Enums\MainEventStatus;
use App\Enums\MainEventType;
use App\Models\MainEvent;
use App\Models\Rivalry;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shape of a main event that the database enforces on its own (D-004).
 */
class MainEventConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_and_played_main_events_are_accepted()
    {
        $scheduled = MainEvent::factory()->create();
        $played = MainEvent::factory()->played()->create();
        $rivalryMatch = MainEvent::factory()->forRivalry(Rivalry::factory()->create())->create();

        $this->assertSame(MainEventStatus::Scheduled, $scheduled->fresh()?->status);
        $this->assertSame($played->wrestler_a_id, $played->winner_id);
        $this->assertSame(MainEventType::Rivalry, $rivalryMatch->fresh()?->type);
    }

    public function test_database_rejects_main_event_against_oneself()
    {
        $wrestler = Wrestler::factory()->create();

        $this->expectException(QueryException::class);

        MainEvent::factory()->create([
            'federation_id' => $wrestler->federation_id,
            'wrestler_a_id' => $wrestler->id,
            'wrestler_b_id' => $wrestler->id,
        ]);
    }

    public function test_database_rejects_imposed_winner_outside_the_match()
    {
        $outsider = Wrestler::factory()->create();

        $this->expectException(QueryException::class);

        MainEvent::factory()->create(['imposed_winner_id' => $outsider->id]);
    }

    public function test_database_rejects_winner_outside_the_match()
    {
        $outsider = Wrestler::factory()->create();

        $this->expectException(QueryException::class);

        MainEvent::factory()->played()->create(['winner_id' => $outsider->id]);
    }

    public function test_database_rejects_played_main_event_without_story()
    {
        $this->expectException(QueryException::class);

        MainEvent::factory()->played()->create(['story' => null]);
    }

    public function test_database_rejects_scheduled_main_event_with_a_winner()
    {
        $event = MainEvent::factory()->create();

        $this->expectException(QueryException::class);

        $event->forceFill(['winner_id' => $event->wrestler_a_id])->save();
    }

    public function test_database_rejects_rivalry_match_without_rivalry()
    {
        $this->expectException(QueryException::class);

        MainEvent::factory()->create(['type' => MainEventType::Rivalry]);
    }

    public function test_deleting_a_title_keeps_its_played_main_event()
    {
        $title = Title::factory()->create();
        $event = MainEvent::factory()->played()->create([
            'federation_id' => $title->federation_id,
            'title_id' => $title->id,
        ]);

        $title->delete();

        $this->assertNull($event->fresh()?->title_id);
        $this->assertNotNull($event->fresh()?->story);
    }
}
