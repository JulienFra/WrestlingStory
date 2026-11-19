<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidPseudos(): array
    {
        return [
            'space' => ['Ju lien'],
            'accent' => ['Julién'],
            'special character' => ['julien@42'],
            'dash' => ['ju-lien'],
            'too short' => ['ju'],
            'too long' => [str_repeat('a', 31)],
            'empty' => [''],
            'array sent instead of text' => [['julien']],
        ];
    }

    #[DataProvider('invalidPseudos')]
    public function test_registration_refuses_an_invalid_pseudo(mixed $pseudo)
    {
        $this->post(route('register.store'), [
            'name' => $pseudo,
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('name');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_refuses_a_pseudo_already_taken_whatever_its_case()
    {
        User::factory()->create(['name' => 'Julien']);

        $this->post(route('register.store'), [
            'name' => 'julien',
            'email' => 'other@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['name' => 'Ce pseudo est déjà pris.']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_database_refuses_a_duplicate_pseudo_even_without_validation()
    {
        User::factory()->create(['name' => 'Julien']);

        $this->expectException(UniqueConstraintViolationException::class);

        User::factory()->create(['name' => 'JULIEN']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidEmails(): array
    {
        return [
            'no at sign' => ['julien'],
            'nothing after at sign' => ['julien@'],
            'domain without dot' => ['julien@site'],
            'space' => ['ju lien@site.be'],
            'empty' => [''],
            'array sent instead of text' => [['julien@site.be']],
        ];
    }

    #[DataProvider('invalidEmails')]
    public function test_registration_refuses_an_invalid_email(mixed $email)
    {
        $this->post(route('register.store'), [
            'name' => 'Julien',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_refuses_a_request_without_any_field()
    {
        // What a player gets after removing the form fields in the browser dev tools (F12).
        $this->post(route('register.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_with_an_email_sent_as_array_gives_an_error_not_a_crash()
    {
        $this->post(route('login.store'), [
            'email' => ['julien@site.be'],
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_forgot_password_with_an_email_sent_as_array_gives_an_error_not_a_crash()
    {
        $this->post(route('password.email'), [
            'email' => ['julien@site.be'],
        ])->assertSessionHasErrors('email');
    }

    public function test_pseudo_error_message_is_in_french()
    {
        $this->post(route('register.store'), [
            'name' => 'Ju lien',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors([
            'name' => 'Le pseudo ne peut contenir que des lettres sans accent, des chiffres et _ (pas d\'espace ni de caractère spécial).',
        ]);
    }
}
