<?php

namespace Tests\Feature\Federations;

use App\Enums\Tier;
use App\Models\Federation;
use App\Models\MainEvent;
use App\Models\Rivalry;
use App\Models\Title;
use App\Models\TitleReign;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FederationTest extends TestCase
{
    use RefreshDatabase;

    private function proUser(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['tier' => Tier::Pro])->save();

        return $user;
    }

    public function test_guest_is_redirected_to_login()
    {
        $federation = Federation::factory()->create();

        $this->get(route('federations.index'))->assertRedirect(route('login'));
        $this->get(route('federations.create'))->assertRedirect(route('login'));
        $this->post(route('federations.store'), ['name' => 'Fédération Fantôme'])->assertRedirect(route('login'));
        $this->get(route('federations.show', $federation))->assertRedirect(route('login'));
        $this->delete(route('federations.destroy', $federation))->assertRedirect(route('login'));

        $this->assertSame(1, Federation::query()->count());
    }

    public function test_player_sees_only_their_federations()
    {
        $user = $this->proUser();
        $mine = Federation::factory()->for($user)->create(['name' => 'Ligue des Marais']);
        Wrestler::factory()->count(2)->for($mine)->create();
        Federation::factory()->for($user)->create(['name' => 'Arène du Nord']);
        Federation::factory()->create(['name' => 'Fédération Voisine']);

        $this->actingAs($user)
            ->get(route('federations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('federations/Index')
                ->has('federations', 2)
                ->where('federations.0.name', 'Arène du Nord')
                ->where('federations.1.name', 'Ligue des Marais')
                ->where('federations.1.wrestlersCount', 2)
            );
    }

    public function test_player_creates_a_federation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('federations.store'), [
            'name' => 'Fédération du Grand Nord',
            'description' => 'Des combats rugueux dans des granges glacées.',
        ]);

        $federation = Federation::query()->sole();
        $response->assertRedirect(route('federations.show', $federation))
            ->assertInertiaFlash('toast.message', 'Fédération créée.');
        $this->assertSame($user->id, $federation->user_id);
        $this->assertSame('Fédération du Grand Nord', $federation->name);

        $this->actingAs($user)
            ->get(route('federations.show', $federation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('federations/Show')
                ->where('federation.name', 'Fédération du Grand Nord')
                ->where('federation.wrestlersCount', 0)
            );
    }

    public function test_federation_name_is_required_and_unique_for_the_player()
    {
        $user = $this->proUser();
        Federation::factory()->for($user)->create(['name' => 'Ligue des Marais']);

        $this->actingAs($user)
            ->post(route('federations.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Donne un nom à ta fédération.']);

        $this->actingAs($user)
            ->post(route('federations.store'), ['name' => 'Ligue des Marais'])
            ->assertSessionHasErrors(['name' => 'Tu as déjà une fédération qui porte ce nom.']);

        $this->assertSame(1, Federation::query()->count());

        // The same name is free for another player.
        $other = User::factory()->create();
        $this->actingAs($other)
            ->post(route('federations.store'), ['name' => 'Ligue des Marais'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $other->federations()->count());
    }

    public function test_free_player_cannot_create_a_second_federation()
    {
        $user = User::factory()->create();
        Federation::factory()->for($user)->create();

        $this->actingAs($user)->get(route('federations.create'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('federations.store'), ['name' => 'Deuxième Fédération'])
            ->assertForbidden();

        $this->assertSame(1, Federation::query()->count());
    }

    public function test_limit_is_checked_before_the_form_fields()
    {
        $user = User::factory()->create();
        Federation::factory()->for($user)->create();

        // An empty name would be a validation error: the tier refusal must come first.
        $this->actingAs($user)
            ->post(route('federations.store'), ['name' => ''])
            ->assertForbidden();
    }

    public function test_pro_player_can_create_up_to_ten_federations()
    {
        $user = $this->proUser();
        Federation::factory()->count(9)->for($user)->create();

        $this->actingAs($user)
            ->post(route('federations.store'), ['name' => 'Dixième Fédération'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $this->assertSame(10, $user->federations()->count());

        $this->actingAs($user)->get(route('federations.create'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('federations.store'), ['name' => 'Onzième Fédération'])
            ->assertForbidden();

        $this->assertSame(10, $user->federations()->count());
    }

    public function test_player_back_on_free_keeps_federations_but_cannot_create()
    {
        $user = $this->proUser();
        Federation::factory()->count(3)->for($user)->create();
        $user->forceFill(['tier' => Tier::Free])->save();

        $this->actingAs($user)
            ->get(route('federations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('federations', 3)
                ->where('count', 3)
                ->where('limit', 1)
                ->where('canCreate', false)
            );

        $this->actingAs($user)
            ->post(route('federations.store'), ['name' => 'Quatrième Fédération'])
            ->assertForbidden();

        $this->assertSame(3, $user->federations()->count());
    }

    public function test_player_cannot_view_or_delete_another_players_federation()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->create();

        $this->actingAs($user)->get(route('federations.show', $federation))->assertForbidden();
        $this->actingAs($user)->delete(route('federations.destroy', $federation))->assertForbidden();

        $this->assertModelExists($federation);
    }

    public function test_deleting_a_federation_deletes_its_content()
    {
        $user = User::factory()->create();
        $federation = Federation::factory()->for($user)->create();
        $title = Title::factory()->for($federation)->create();
        TitleReign::factory()->create(['title_id' => $title->id]);
        Rivalry::factory()->for($federation)->create();
        MainEvent::factory()->for($federation)->create();
        $otherFederation = Federation::factory()->create();
        Wrestler::factory()->for($otherFederation)->create();

        $this->actingAs($user)
            ->delete(route('federations.destroy', $federation))
            ->assertRedirect(route('federations.index'))
            ->assertInertiaFlash('toast.message', 'Fédération supprimée.');

        $this->assertModelMissing($federation);
        $this->assertSame(0, Title::query()->count());
        $this->assertSame(0, TitleReign::query()->count());
        $this->assertSame(0, Rivalry::query()->count());
        $this->assertSame(0, MainEvent::query()->count());
        $this->assertSame(1, Wrestler::query()->count());
        $this->assertModelExists($otherFederation);
    }

    public function test_index_tells_whether_player_can_create()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('federations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('federations/Index')
                ->where('count', 0)
                ->where('limit', 1)
                ->where('tier', 'free')
                ->where('canCreate', true)
                ->where('limitMessage', null)
            );

        Federation::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('federations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('count', 1)
                ->where('limit', 1)
                ->where('canCreate', false)
                ->where('limitMessage', "Ton palier Gratuit permet 1 fédération. Passe en Pro pour en créer jusqu'à 10.")
            );
    }

    public function test_user_id_from_the_form_is_ignored()
    {
        $user = User::factory()->create();
        $victim = User::factory()->create();

        $this->actingAs($user)->post(route('federations.store'), [
            'name' => 'Fédération Pirate',
            'user_id' => $victim->id,
        ]);

        $this->assertSame(0, $victim->federations()->count());
        $this->assertSame(1, $user->federations()->count());
    }
}
