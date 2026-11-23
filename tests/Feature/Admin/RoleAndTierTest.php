<?php

namespace Tests\Feature\Admin;

use App\Enums\Tier;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RoleAndTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_player_is_a_simple_user_on_the_free_tier()
    {
        $this->skipUnlessFortifyHas(Features::registration());

        $this->post(route('register.store'), [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(Tier::Free, $user->tier);
        $this->assertFalse($user->isAdmin());
    }

    public function test_player_cannot_become_admin_or_pro_by_adding_fields_to_the_registration()
    {
        $this->skipUnlessFortifyHas(Features::registration());

        $this->post(route('register.store'), [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'tier' => 'pro',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(Tier::Free, $user->tier);
    }

    public function test_player_cannot_become_admin_or_pro_by_adding_fields_to_the_profile_update()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'TestUser',
                'email' => 'test@example.com',
                'role' => 'admin',
                'tier' => 'pro',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('TestUser', $user->name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(Tier::Free, $user->tier);
    }
}
