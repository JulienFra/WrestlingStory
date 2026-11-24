<?php

namespace Tests\Feature\Ai;

use App\Ai\AiCompletion;
use App\Ai\AiErrorKind;
use App\Ai\AiException;
use App\Ai\AiGateway;
use App\Ai\AiJsonCompletion;
use App\Ai\AiJsonSchema;
use App\Ai\AiProvider;
use App\Ai\FakeAiClient;
use App\Ai\QuotaExceededException;
use App\Models\AiCall;
use App\Models\Setting;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AiGatewayJsonTest extends TestCase
{
    use RefreshDatabase;

    private AiGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Whatever the local .env says, these tests start on the fake provider.
        config(['ai.default_provider' => 'fake', 'ai.model' => 'test/model-small']);
        $this->travelTo('2026-11-25 10:00:00');
        $this->gateway = new AiGateway;
    }

    /**
     * @return array<string, mixed>
     */
    private function matchSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'winner_id' => ['type' => 'integer', 'enum' => [12, 7]],
                'story' => ['type' => 'string', 'maxLength' => 20],
                'follow_up' => ['type' => ['string', 'null']],
                'title_change' => ['type' => 'boolean'],
                'highlights' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['winner_id', 'story', 'follow_up', 'title_change', 'highlights'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param  (Closure(array<string, mixed>): bool)|null  $isUsable
     */
    private function callMatch(User $user, ?Closure $isUsable = null): AiJsonCompletion
    {
        return $this->gateway->completeJson(
            $user, 'main_event', 'Tu racontes des matchs.', 'Un match', 300,
            'main_event', $this->matchSchema(), $isUsable ?? fn (array $data) => true,
        );
    }

    /**
     * Replaces the fake provider by one that answers $text and counts its calls.
     */
    private function stubClient(string $text): FakeAiClient
    {
        $stub = new class($text) extends FakeAiClient
        {
            public int $calls = 0;

            public function __construct(private string $text)
            {
                parent::__construct(AiProvider::Fake);
            }

            public function complete(string $system, string $prompt, int $maxTokens, ?AiJsonSchema $jsonSchema = null): AiCompletion
            {
                $this->calls++;

                return new AiCompletion($this->text, 'stub-model', 30, 12, 0.0005);
            }
        };

        $this->app->bind(FakeAiClient::class, fn () => $stub);

        return $stub;
    }

    public function test_fake_provider_answers_data_built_from_the_schema()
    {
        Http::fake();
        $user = User::factory()->create();

        $completion = $this->callMatch($user);

        $this->assertSame([
            'winner_id' => 12,
            'story' => 'Un récit inventé par',
            'follow_up' => 'Un récit inventé par le faux fournisseur, sans appel au modèle.',
            'title_change' => true,
            'highlights' => ['Un récit inventé par le faux fournisseur, sans appel au modèle.'],
        ], $completion->data);
        $this->assertSame('fake', $completion->model);
        $this->assertSame(0.0, $completion->cost);
        $call = AiCall::sole();
        $this->assertSame($call->id, $completion->aiCallId);
        $this->assertTrue($call->success);
        $this->assertSame('main_event', $call->feature);
        Http::assertNothingSent();
    }

    public function test_the_domain_check_receives_the_decoded_object()
    {
        $user = User::factory()->create();
        $seen = null;

        $this->callMatch($user, function (array $data) use (&$seen) {
            $seen = $data;

            return true;
        });

        $this->assertSame(12, $seen['winner_id'] ?? null);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableAnswers(): array
    {
        return [
            'not json' => ['Le vainqueur est le catcheur 12.'],
            'truncated json' => ['{"winner_id": 12, "story": "Un mat'],
            'json list' => ['[{"winner_id": 12}]'],
            'json string' => ['"winner_id"'],
            'empty object' => ['{}'],
        ];
    }

    #[DataProvider('unusableAnswers')]
    public function test_answer_that_is_not_a_json_object_is_refused_and_logged(string $text)
    {
        $user = User::factory()->create();
        $this->stubClient($text);

        $this->expectInvalidResponse(fn () => $this->callMatch($user));

        $call = AiCall::sole();
        $this->assertFalse($call->success);
        $this->assertSame('invalid_response', $call->error);
        $this->assertSame(30, $call->input_tokens, 'The tokens of a refused answer were still paid for.');
        $this->assertSame(0.0005, $call->cost);
    }

    public function test_object_refused_by_the_domain_check_is_refused_and_logged()
    {
        $user = User::factory()->create();
        $this->stubClient('{"winner_id": 99, "story": "Un match"}');

        $this->expectInvalidResponse(fn () => $this->callMatch($user, fn (array $data) => in_array($data['winner_id'] ?? null, [12, 7], true)));

        $call = AiCall::sole();
        $this->assertSame('invalid_response', $call->error);
        $this->assertSame(12, $call->output_tokens);
    }

    public function test_quota_is_checked_before_any_call()
    {
        $user = User::factory()->create();
        $stub = $this->stubClient('{"winner_id": 12}');
        AiCall::factory()->count(10)->for($user)->create();

        try {
            $this->callMatch($user);
            $this->fail('A QuotaExceededException was expected.');
        } catch (QuotaExceededException $exception) {
            $this->assertSame(10, $exception->dailyLimit);
        }

        $this->assertSame(0, $stub->calls);
        $refused = AiCall::where('error', AiGateway::QUOTA_ERROR)->sole();
        $this->assertFalse($refused->success);
        $this->assertSame('main_event', $refused->feature);
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
    public function test_each_failing_fake_throws_its_error_in_json_mode(AiProvider $provider, AiErrorKind $kind)
    {
        $user = User::factory()->create();
        Setting::set('ai.provider', $provider->value);

        try {
            $this->callMatch($user);
            $this->fail('An AiException was expected.');
        } catch (AiException $exception) {
            $this->assertSame($kind, $exception->kind);
        }

        $call = AiCall::sole();
        $this->assertFalse($call->success);
        $this->assertSame($kind->value, $call->error);
    }

    public function test_real_provider_receives_the_schema()
    {
        $user = User::factory()->create(['email' => 'joueur@example.com']);
        Setting::set('ai.provider', AiProvider::OpenRouter->value);
        config(['services.openrouter.key' => 'test-key', 'services.openrouter.url' => 'https://openrouter.test/api/v1']);
        Http::fake(['openrouter.test/*' => Http::response([
            'model' => 'test/model-small',
            'choices' => [['message' => ['content' => '{"winner_id": 7, "story": "Un match"}']]],
            'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 5, 'cost' => 0.0001],
        ])]);

        $completion = $this->callMatch($user);

        $this->assertSame(['winner_id' => 7, 'story' => 'Un match'], $completion->data);
        $this->assertSame(AiCall::sole()->id, $completion->aiCallId);
        Http::assertSent(fn ($request) => $request['response_format']['json_schema']['name'] === 'main_event'
            && $request['response_format']['json_schema']['schema'] === $this->matchSchema()
            && ! str_contains($request->body(), 'joueur@example.com'));
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
}
