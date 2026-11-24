<?php

namespace Tests\Feature\Promo;

use App\Ai\AiClient;
use App\Ai\AiCompletion;
use App\Ai\AiErrorKind;
use App\Ai\AiJsonSchema;
use App\Ai\AiProvider;
use App\Ai\FakeAiClient;
use App\Ai\Prompts\PromoPrompt;
use App\Models\AiCall;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PromoTest extends TestCase
{
    use RefreshDatabase;

    private const IDEA = 'Un bûcheron géant qui parle aux arbres et veut venger sa forêt.';

    protected function setUp(): void
    {
        parent::setUp();

        // Whatever the local .env says, these tests run on the fake provider: no real request is possible.
        config(['ai.default_provider' => 'fake']);
        $this->travelTo('2026-11-23 10:00:00');
    }

    /**
     * Replaces the fake provider by a client that counts its calls and remembers what it received.
     */
    private function stubClient(): object
    {
        $stub = new class implements AiClient
        {
            public int $calls = 0;

            public string $system = '';

            public string $prompt = '';

            public function complete(string $system, string $prompt, int $maxTokens, ?AiJsonSchema $jsonSchema = null): AiCompletion
            {
                $this->calls++;
                $this->system = $system;
                $this->prompt = $prompt;

                return new AiCompletion('Je suis la tempête des forêts !', 'stub-model', 30, 12, 0.0005);
            }
        };

        $this->app->bind(FakeAiClient::class, fn () => $stub);

        return $stub;
    }

    public function test_guest_is_redirected_to_the_login_page()
    {
        $this->get(route('promo.create'))->assertRedirect(route('login'));
        $this->post(route('promo.store'), ['idea' => self::IDEA])->assertRedirect(route('login'));
        $this->assertSame(0, AiCall::count());
    }

    public function test_page_shows_the_remaining_quota_of_the_day()
    {
        $user = User::factory()->create();
        AiCall::factory()->count(3)->for($user)->create();

        $this->actingAs($user)
            ->get(route('promo.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('promo/Create')
                ->where('remainingToday', 7)
                ->where('dailyLimit', 10)
                ->where('result', null));
    }

    public function test_successful_generation_logs_the_call_and_flashes_the_result()
    {
        $user = User::factory()->create(['name' => 'pseudo1234', 'email' => 'joueur@example.com']);
        $stub = $this->stubClient();

        $this->actingAs($user)
            ->post(route('promo.store'), ['idea' => self::IDEA])
            ->assertRedirect(route('promo.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('promo', [
                'text' => 'Je suis la tempête des forêts !',
                'model' => 'stub-model',
                'aiGenerated' => true,
            ]);

        $call = AiCall::sole();
        $this->assertSame('promo', $call->feature);
        $this->assertSame($user->id, $call->user_id);
        $this->assertTrue($call->success);

        $this->assertSame(1, $stub->calls);
        $this->assertSame(PromoPrompt::SYSTEM, $stub->system);
        $this->assertSame(self::IDEA, $stub->prompt);
        $this->assertStringNotContainsString('pseudo1234', $stub->system.$stub->prompt);
        $this->assertStringNotContainsString('joueur@example.com', $stub->system.$stub->prompt);
    }

    public function test_page_shows_the_flashed_result_and_the_quota_after_a_generation()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->post(route('promo.store'), ['idea' => self::IDEA])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('promo/Create')
                ->where('remainingToday', 9)
                ->where('result.model', 'fake')
                ->where('result.aiGenerated', true)
                ->where('oldIdea', self::IDEA)
                ->has('result.text'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIdeas(): array
    {
        return [
            'missing' => [''],
            'too short' => ['Un catch'],
            'too long' => [str_repeat('a', 501)],
        ];
    }

    #[DataProvider('invalidIdeas')]
    public function test_invalid_idea_is_refused_without_calling_the_model(string $idea)
    {
        $user = User::factory()->create();
        $stub = $this->stubClient();

        $this->actingAs($user)
            ->from(route('promo.create'))
            ->post(route('promo.store'), ['idea' => $idea])
            ->assertRedirect(route('promo.create'))
            ->assertSessionHasErrors('idea');

        $this->assertSame(0, $stub->calls);
        $this->assertSame(0, AiCall::count());
    }

    /**
     * @return array<string, array{AiProvider, AiErrorKind}>
     */
    public static function failingProviders(): array
    {
        return [
            'timeout' => [AiProvider::FakeTimeout, AiErrorKind::Timeout],
            'outage' => [AiProvider::FakeOutage, AiErrorKind::ProviderDown],
            'credit' => [AiProvider::FakeCredit, AiErrorKind::CreditExhausted],
            'invalid' => [AiProvider::FakeInvalid, AiErrorKind::InvalidResponse],
        ];
    }

    #[DataProvider('failingProviders')]
    public function test_ai_error_comes_back_in_french_and_keeps_the_idea(AiProvider $provider, AiErrorKind $kind)
    {
        $user = User::factory()->create();
        Setting::set('ai.provider', $provider->value);

        $this->actingAs($user)
            ->from(route('promo.create'))
            ->post(route('promo.store'), ['idea' => self::IDEA])
            ->assertRedirect(route('promo.create'))
            ->assertSessionHasErrors(['ai' => $kind->message()])
            ->assertSessionHasInput('idea', self::IDEA)
            ->assertSessionMissing('promo');

        $this->assertSame($kind->value, AiCall::sole()->error);
    }

    public function test_free_player_over_the_quota_gets_the_quota_message_without_calling_the_model()
    {
        $user = User::factory()->create();
        AiCall::factory()->count(10)->for($user)->create();
        $stub = $this->stubClient();

        $this->actingAs($user)
            ->from(route('promo.create'))
            ->post(route('promo.store'), ['idea' => self::IDEA])
            ->assertRedirect(route('promo.create'))
            ->assertSessionHasErrors(['ai' => "Tu as utilisé tes 10 générations d'IA d'aujourd'hui. Reviens demain ou demande un palier supérieur."])
            ->assertSessionHasInput('idea', self::IDEA);

        $this->assertSame(0, $stub->calls);
        $this->assertSame(1, AiCall::where('error', 'quota')->count());
    }
}
