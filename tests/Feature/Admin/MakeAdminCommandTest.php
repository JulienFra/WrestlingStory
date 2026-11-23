<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_makes_an_existing_user_admin_even_with_the_email_in_uppercase()
    {
        $user = User::factory()->create(['email' => 'julien@example.com']);

        $this->artisan('app:make-admin', ['email' => 'JULIEN@Example.com'])
            ->expectsOutput('julien@example.com est maintenant administrateur.')
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $user->refresh()->role);
    }

    public function test_command_fails_cleanly_on_an_unknown_email()
    {
        User::factory()->create(['email' => 'julien@example.com']);

        $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])
            ->expectsOutput("Aucun utilisateur avec l'e-mail nobody@example.com.")
            ->assertFailed();

        $this->assertSame(0, User::where('role', UserRole::Admin)->count());
    }

    public function test_revoke_option_gives_back_the_simple_user_role()
    {
        $user = User::factory()->admin()->create(['email' => 'julien@example.com']);

        $this->artisan('app:make-admin', ['email' => 'julien@example.com', '--revoke' => true])
            ->expectsOutput("julien@example.com n'est plus administrateur.")
            ->assertSuccessful();

        $this->assertSame(UserRole::User, $user->refresh()->role);
    }
}
