<?php

namespace Tests\Feature\Ai;

use App\Ai\AiClient;
use App\Ai\AiCompletion;
use App\Ai\AiErrorKind;
use App\Ai\AiException;
use App\Ai\AiGateway;
use App\Ai\AiJsonSchema;
use App\Ai\AiProvider;
use App\Ai\FakeAiClient;
use App\Ai\QuotaExceededException;
use App\Enums\Tier;
use App\Models\AiCall;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGatewayTest extends TestCase
{
    use RefreshDatabase;

    private AiGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Whatever the local .env says, these tests start on the fake provider.
        config(['ai.default_provider' => 'fake', 'ai.model' => 'test/model-small']);
        $this->travelTo('2026-11-23 10:00:00');
        $this->gateway = new AiGateway;
    }

    /**
     * Replaces the fake provider by a client that counts its calls and answers $text.
     */
    private function stubClient(string $text = 'Une promo de test.'): object
    {
        $stub = new class($text) implements AiClient
        {
            public int $calls = 0;

            public function __construct(private string $text) {}

            public function complete(string $system, string $prompt, int $maxTokens, ?AiJsonSchema $jsonSchema = null): AiCompletion
            {
                $this->calls++;

                return new AiCompletion($this->text, 'stub-model', 30, 12, 0.0005);
            }
        };

        $this->app->bind(FakeAiClient::class, fn () => $stub);

        return $stub;
    }

    private function callPromo(User $user, int $maxChars = 500): AiCompletion
    {
        return $this->gateway->complete($user, 'promo', 'Tu écris des promos.', 'Un catcheur masqué', 200, $maxChars);
    }

    public function test_successful_call_is_logged_with_all_its_details()
    {
        $user = User::factory()->create();
        $this->stubClient('  Je suis le roi du ring !  ');

        $completion = $this->callPromo($user);

        $this->assertSame('Je suis le roi du ring !', $completion->text);
        $call = AiCall::sole();
        $this->assertSame($user->id, $call->user_id);
        $this->assertSame('promo', $call->feature);
        $this->assertSame('fake', $call->provider);
        $this->assertSame('stub-model', $call->model);
        $this->assertSame(30, $call->input_tokens);
        $this->assertSame(12, $call->output_tokens);
        $this->assertSame(0.0005, $call->cost);
        $this->assertGreaterThanOrEqual(0, $call->duration_ms);
        $this->assertTrue($call->success);
        $this->assertNull($call->error);
    }

    public function test_real_provider_never_receives_the_player_email_or_name()
    {
        $user = User::factory()->create(['name' => 'pseudo1234', 'email' => 'joueur@example.com']);
        Setting::set('ai.provider', AiProvider::OpenRouter->value);
        config(['services.openrouter.key' => 'test-key', 'services.openrouter.url' => 'https://openrouter.test/api/v1']);
        Http::fake(['openrouter.test/*' => Http::response([
            'model' => 'test/model-small',
            'choices' => [['message' => ['content' => 'Promo réelle']]],
            'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 5, 'cost' => 0.0001],
        ])]);

        $this->callPromo($user);

        Http::assertSent(fn (Request $request) => ! str_contains($request->body(), 'joueur@example.com')
            && ! str_contains($request->body(), 'pseudo1234')
            && $request['provider'] === ['zdr' => true, 'data_collection' => 'deny']
            && $request['model'] === 'test/model-small');
        $call = AiCall::sole();
        $this->assertSame('openrouter', $call->provider);
        $this->assertSame(0.0001, $call->cost);
    }

    public function test_failed_call_is_logged_and_the_error_is_thrown_again()
    {
        $user = User::factory()->create();
        Setting::set('ai.provider', AiProvider::FakeCredit->value);

        try {
            $this->callPromo($user);
            $this->fail('An AiException was expected.');
        } catch (AiException $exception) {
            $this->assertSame(AiErrorKind::CreditExhausted, $exception->kind);
        }

        $call = AiCall::sole();
        $this->assertFalse($call->success);
        $this->assertSame('credit_exhausted', $call->error);
        $this->assertSame('fake', $call->provider);
        $this->assertSame(0, $call->input_tokens);
    }

    public function test_blank_answer_is_refused_and_logged_as_invalid_response()
    {
        $user = User::factory()->create();
        $this->stubClient("   \n  ");

        $this->expectInvalidResponse(fn () => $this->callPromo($user));

        $call = AiCall::sole();
        $this->assertFalse($call->success);
        $this->assertSame('invalid_response', $call->error);
        $this->assertSame(30, $call->input_tokens, 'The tokens of a refused answer were still paid for.');
    }

    public function test_too_long_answer_is_refused_and_logged_as_invalid_response()
    {
        $user = User::factory()->create();
        $this->stubClient(str_repeat('a', 101));

        $this->expectInvalidResponse(fn () => $this->callPromo($user, maxChars: 100));

        $this->assertSame('invalid_response', AiCall::sole()->error);
    }

    private function expectInvalidResponse(callable $call): void
    {
        try {
            $call();
            $this->fail('An AiException was expected.');
        } catch (AiException $exception) {
            $this->assertSame(AiErrorKind::InvalidResponse, $exception->kind);
        }
    }

    public function test_free_player_is_stopped_at_the_eleventh_call_without_calling_the_model()
    {
        $user = User::factory()->create();
        $stub = $this->stubClient();

        for ($i = 0; $i < 10; $i++) {
            $this->callPromo($user);
        }

        try {
            $this->callPromo($user);
            $this->fail('A QuotaExceededException was expected.');
        } catch (QuotaExceededException $exception) {
            $this->assertSame(10, $exception->dailyLimit);
            $this->assertStringContainsString('tes 10 générations', $exception->getMessage());
        }

        $this->assertSame(10, $stub->calls);
        $refused = AiCall::where('error', 'quota')->sole();
        $this->assertFalse($refused->success);
        $this->assertSame(0, $refused->duration_ms);
        $this->assertSame(0, $refused->input_tokens);
        $this->assertSame(11, AiCall::count());
    }

    public function test_pro_player_has_one_hundred_calls_a_day()
    {
        $user = User::factory()->create();
        $user->tier = Tier::Pro;
        $user->save();
        $stub = $this->stubClient();
        AiCall::factory()->count(99)->for($user)->create();

        $this->assertSame(100, $this->gateway->dailyLimit($user));
        $this->callPromo($user);
        $this->assertSame(1, $stub->calls);

        $this->expectException(QuotaExceededException::class);
        $this->callPromo($user);
    }

    public function test_failed_calls_count_in_the_quota_but_quota_refusals_do_not()
    {
        $user = User::factory()->create();
        AiCall::factory()->count(2)->for($user)->create(['success' => false, 'error' => 'timeout']);
        AiCall::factory()->count(5)->for($user)->quotaRefused()->create();

        $this->assertSame(8, $this->gateway->remainingToday($user));
    }

    public function test_yesterdays_calls_do_not_count()
    {
        $user = User::factory()->create();
        $stub = $this->stubClient();

        $this->travelTo('2026-11-22 23:59:00');
        AiCall::factory()->count(10)->for($user)->create();
        $this->assertSame(0, $this->gateway->remainingToday($user));

        $this->travelTo('2026-11-23 00:01:00');
        $this->assertSame(10, $this->gateway->remainingToday($user));
        $this->callPromo($user);
        $this->assertSame(1, $stub->calls);
    }

    public function test_remaining_today_counts_down_and_never_goes_negative()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        AiCall::factory()->count(3)->for($user)->create();
        AiCall::factory()->count(4)->for($other)->create();

        $this->assertSame(7, $this->gateway->remainingToday($user));

        AiCall::factory()->count(9)->for($user)->create();

        $this->assertSame(0, $this->gateway->remainingToday($user));
    }

    public function test_admin_setting_chooses_the_provider()
    {
        $this->assertSame(AiProvider::Fake, $this->gateway->currentProvider());

        Setting::set('ai.provider', 'fake_timeout');
        $this->assertSame(AiProvider::FakeTimeout, $this->gateway->currentProvider());

        Setting::set('ai.provider', 'openrouter');
        $this->assertSame(AiProvider::OpenRouter, $this->gateway->currentProvider());
    }

    public function test_unknown_setting_falls_back_to_the_config()
    {
        config(['ai.default_provider' => 'fake_outage']);
        Setting::set('ai.provider', 'gpt-in-a-box');

        $this->assertSame(AiProvider::FakeOutage, $this->gateway->currentProvider());
    }

    public function test_deleting_the_player_keeps_their_calls_anonymously()
    {
        $user = User::factory()->create();
        $this->stubClient();
        $this->callPromo($user);

        $user->delete();

        $call = AiCall::sole();
        $this->assertNull($call->user_id);
        $this->assertSame('promo', $call->feature);
    }
}
