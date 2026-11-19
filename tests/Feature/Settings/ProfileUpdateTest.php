<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'TestUser',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('TestUser', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_is_stored_in_lowercase_so_the_user_can_still_log_in()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'TestUser',
                'email' => 'Test@Example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('test@example.com', $user->refresh()->email);

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => 'Test@Example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_must_be_unique_regardless_of_case()
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'TestUser',
                'email' => 'Taken@Example.com',
            ]);

        $response
            ->assertSessionHasErrors('email')
            ->assertRedirect(route('profile.edit'));
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'TestUser',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_profile_update_refuses_a_pseudo_with_a_space()
    {
        $user = User::factory()->create(['name' => 'Julien']);

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Ju lien',
                'email' => $user->email,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame('Julien', $user->refresh()->name);
    }

    public function test_profile_update_refuses_a_pseudo_taken_by_another_player_whatever_its_case()
    {
        User::factory()->create(['name' => 'Julien']);
        $user = User::factory()->create(['name' => 'Florentin']);

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'JULIEN',
                'email' => $user->email,
            ])
            ->assertSessionHasErrors(['name' => 'Ce pseudo est déjà pris.']);

        $this->assertSame('Florentin', $user->refresh()->name);
    }

    public function test_player_can_change_the_case_of_their_own_pseudo()
    {
        $user = User::factory()->create(['name' => 'julien']);

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Julien',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Julien', $user->refresh()->name);
    }

    public function test_profile_update_refuses_an_email_without_a_real_domain()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => 'julien@site',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }
}
