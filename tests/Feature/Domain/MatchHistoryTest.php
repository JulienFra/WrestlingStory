<?php

namespace Tests\Feature\Domain;

use App\Models\AiCall;
use App\Models\Federation;
use App\Models\MainEvent;
use App\Models\Rivalry;
use App\Models\Title;
use App\Models\TitleReign;
use App\Models\Wrestler;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The match history survives: the database refuses to delete a wrestler who fought or held a title,
 * or a rivalry that has matches. Deleting a federation or a player still deletes everything inside.
 */
class MatchHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrestler_with_a_scheduled_main_event_cannot_be_deleted()
    {
        $mainEvent = MainEvent::factory()->create();

        $this->expectException(QueryException::class);

        Wrestler::findOrFail($mainEvent->wrestler_b_id)->delete();
    }

    public function test_wrestler_with_a_played_main_event_cannot_be_deleted()
    {
        $mainEvent = MainEvent::factory()->played()->create();

        $this->expectException(QueryException::class);

        Wrestler::findOrFail($mainEvent->wrestler_a_id)->delete();
    }

    public function test_wrestler_with_a_title_reign_cannot_be_deleted()
    {
        $reign = TitleReign::factory()->ended()->create();

        $this->expectException(QueryException::class);

        Wrestler::findOrFail($reign->wrestler_id)->delete();
        $this->checkDeferredConstraints();
    }

    public function test_rivalry_with_a_main_event_cannot_be_deleted()
    {
        $rivalry = Rivalry::factory()->create();
        MainEvent::factory()->forRivalry($rivalry)->create();

        $this->expectException(QueryException::class);

        $rivalry->delete();
    }

    public function test_wrestler_without_match_nor_reign_can_be_deleted()
    {
        $wrestler = Wrestler::factory()->create();

        $wrestler->delete();

        $this->assertModelMissing($wrestler);
    }

    public function test_deleting_a_federation_deletes_its_whole_history()
    {
        $federation = $this->federationWithHistory();

        $federation->delete();
        $this->checkDeferredConstraints();

        $this->assertEmptyGame();
    }

    public function test_deleting_the_player_deletes_their_federations_and_history()
    {
        $federation = $this->federationWithHistory();

        $federation->user->delete();
        $this->checkDeferredConstraints();

        $this->assertModelMissing($federation);
        $this->assertEmptyGame();
    }

    public function test_main_event_keeps_its_ai_call()
    {
        $call = AiCall::factory()->create();
        $mainEvent = MainEvent::factory()->create();
        $mainEvent->ai_call_id = $call->id;
        $mainEvent->save();

        $this->assertTrue($mainEvent->aiCall->is($call));

        $call->delete();

        $this->assertNull($mainEvent->fresh()?->ai_call_id);
    }

    public function test_public_token_is_unique()
    {
        $first = Federation::factory()->create();
        $second = Federation::factory()->create();
        $first->public_token = str_repeat('a', 64);
        $first->save();

        $this->expectException(QueryException::class);

        $second->public_token = str_repeat('a', 64);
        $second->save();
    }

    public function test_federations_without_public_link_can_coexist()
    {
        Federation::factory()->count(2)->create();

        $this->assertSame(2, Federation::whereNull('public_token')->count());
    }

    public function test_public_token_never_appears_in_the_serialized_federation()
    {
        $federation = Federation::factory()->create();
        $federation->public_token = str_repeat('b', 64);
        $federation->save();

        $this->assertArrayNotHasKey('public_token', $federation->fresh()?->toArray() ?? []);
        $this->assertStringNotContainsString(str_repeat('b', 64), $federation->toJson());
    }

    /**
     * A federation with wrestlers, a title, an ended and an open reign, a rivalry, a played rivalry match
     * and a played simple match.
     */
    private function federationWithHistory(): Federation
    {
        $federation = Federation::factory()->create();
        $title = Title::factory()->create(['federation_id' => $federation->id]);
        TitleReign::factory()->ended()->create(['title_id' => $title->id]);
        $champion = TitleReign::factory()->create(['title_id' => $title->id])->wrestler_id;
        $challenger = Wrestler::factory()->create(['federation_id' => $federation->id]);
        $rivalry = Rivalry::factory()->create([
            'federation_id' => $federation->id,
            'wrestler_a_id' => $champion,
            'wrestler_b_id' => $challenger->id,
            'title_id' => $title->id,
        ]);
        MainEvent::factory()->forRivalry($rivalry)->played()->create();
        MainEvent::factory()->played()->create(['federation_id' => $federation->id]);

        return $federation;
    }

    /**
     * title_reigns.wrestler_id is checked at commit, and RefreshDatabase never commits: check it now.
     */
    private function checkDeferredConstraints(): void
    {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }

    private function assertEmptyGame(): void
    {
        foreach (['federations', 'wrestlers', 'titles', 'title_reigns', 'rivalries', 'main_events'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
