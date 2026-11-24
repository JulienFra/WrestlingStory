<?php

namespace Tests\Feature\Domain;

use App\Models\Federation;
use App\Models\Rivalry;
use App\Models\Title;
use App\Models\TitleReign;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FederationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_user_deletes_federations_and_content()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->for($user)->create();
        $title = Title::factory()->for($federation)->create();
        TitleReign::factory()->create(['title_id' => $title->id]);
        Rivalry::factory()->for($federation)->create();

        $user->delete();

        $this->assertSame(0, Federation::query()->count());
        $this->assertSame(0, Wrestler::query()->count());
        $this->assertSame(0, Title::query()->count());
        $this->assertSame(0, TitleReign::query()->count());
        $this->assertSame(0, Rivalry::query()->count());
    }

    public function test_title_current_reign_returns_open_reign()
    {
        $title = Title::factory()->create();
        TitleReign::factory()->ended()->create(['title_id' => $title->id]);
        $open = TitleReign::factory()->create(['title_id' => $title->id]);

        $this->assertTrue($title->currentReign?->is($open));
    }

    public function test_vacant_title_has_no_current_reign()
    {
        $title = Title::factory()->create();
        TitleReign::factory()->ended()->create(['title_id' => $title->id]);

        $this->assertNull($title->currentReign);
    }

    public function test_factories_keep_wrestlers_in_the_same_federation()
    {
        $rivalry = Rivalry::factory()->create();
        $reign = TitleReign::factory()->create();

        $this->assertSame($rivalry->federation_id, $rivalry->wrestlerA->federation_id);
        $this->assertSame($rivalry->federation_id, $rivalry->wrestlerB->federation_id);
        $this->assertSame($reign->title->federation_id, $reign->wrestler->federation_id);
    }
}
