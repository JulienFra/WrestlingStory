<?php

namespace Tests\Feature\Ai;

use App\Ai\AiErrorKind;
use App\Ai\AiException;
use App\Ai\OpenRouterClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.url' => 'https://openrouter.test/api/v1',
            'ai.model' => 'test/model-small',
            'ai.timeout' => 20,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function successBody(): array
    {
        return [
            'model' => 'test/model-small-2511',
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'Je suis le roi du ring !']]],
            'usage' => ['prompt_tokens' => 42, 'completion_tokens' => 17, 'cost' => 0.00012345],
        ];
    }

    private function assertErrorKind(AiErrorKind $expected): void
    {
        try {
            (new OpenRouterClient)->complete('Système', 'Un catcheur masqué', 200);
            $this->fail('An AiException was expected.');
        } catch (AiException $exception) {
            $this->assertSame($expected, $exception->kind);
            $this->assertSame($expected->message(), $exception->getMessage());
        }
    }

    public function test_success_reads_text_tokens_cost_and_model()
    {
        Http::fake(['openrouter.test/*' => Http::response($this->successBody())]);

        $completion = (new OpenRouterClient)->complete('Tu écris des promos.', 'Un catcheur masqué venu du froid', 200);

        $this->assertSame('Je suis le roi du ring !', $completion->text);
        $this->assertSame('test/model-small-2511', $completion->model);
        $this->assertSame(42, $completion->inputTokens);
        $this->assertSame(17, $completion->outputTokens);
        $this->assertSame(0.00012345, $completion->cost);
    }

    public function test_request_asks_for_zero_data_retention_and_no_data_collection()
    {
        Http::fake(['openrouter.test/*' => Http::response($this->successBody())]);

        (new OpenRouterClient)->complete('Tu écris des promos.', 'Un catcheur masqué venu du froid', 200);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://openrouter.test/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'test/model-small'
                && $request['max_tokens'] === 200
                && $request['provider'] === ['zdr' => true, 'data_collection' => 'deny']
                && $request['usage'] === ['include' => true]
                && $request['messages'] === [
                    ['role' => 'system', 'content' => 'Tu écris des promos.'],
                    ['role' => 'user', 'content' => 'Un catcheur masqué venu du froid'],
                ];
        });
    }

    public function test_missing_model_and_cost_fall_back_to_requested_model_and_zero()
    {
        Http::fake(['openrouter.test/*' => Http::response([
            'choices' => [['message' => ['content' => 'Promo']]],
        ])]);

        $completion = (new OpenRouterClient)->complete('Système', 'Prompt', 200);

        $this->assertSame('test/model-small', $completion->model);
        $this->assertSame(0, $completion->inputTokens);
        $this->assertSame(0.0, $completion->cost);
    }

    public function test_payment_required_means_credit_exhausted()
    {
        Http::fake(['openrouter.test/*' => Http::response(['error' => ['message' => 'Insufficient credits']], 402)]);

        $this->assertErrorKind(AiErrorKind::CreditExhausted);
    }

    public function test_server_error_means_provider_down()
    {
        Http::fake(['openrouter.test/*' => Http::response('', 500)]);

        $this->assertErrorKind(AiErrorKind::ProviderDown);
    }

    public function test_rate_limit_means_provider_down()
    {
        Http::fake(['openrouter.test/*' => Http::response(['error' => ['message' => 'Rate limited']], 429)]);

        $this->assertErrorKind(AiErrorKind::ProviderDown);
    }

    public function test_request_timeout_status_means_timeout()
    {
        Http::fake(['openrouter.test/*' => Http::response('', 408)]);

        $this->assertErrorKind(AiErrorKind::Timeout);
    }

    public function test_connection_timed_out_means_timeout()
    {
        Http::fake(['openrouter.test/*' => Http::failedConnection('cURL error 28: Operation timed out after 20001 milliseconds')]);

        $this->assertErrorKind(AiErrorKind::Timeout);
    }

    public function test_other_connection_error_means_provider_down()
    {
        Http::fake(['openrouter.test/*' => Http::failedConnection('cURL error 6: Could not resolve host: openrouter.test')]);

        $this->assertErrorKind(AiErrorKind::ProviderDown);
    }

    public function test_answer_without_choices_means_invalid_response()
    {
        Http::fake(['openrouter.test/*' => Http::response(['id' => 'gen-1', 'usage' => ['prompt_tokens' => 3]])]);

        $this->assertErrorKind(AiErrorKind::InvalidResponse);
    }

    public function test_answer_with_blank_content_means_invalid_response()
    {
        Http::fake(['openrouter.test/*' => Http::response(['choices' => [['message' => ['content' => "  \n "]]]])]);

        $this->assertErrorKind(AiErrorKind::InvalidResponse);
    }

    public function test_missing_key_means_provider_down_without_any_request()
    {
        config(['services.openrouter.key' => null]);
        Http::fake();

        $this->assertErrorKind(AiErrorKind::ProviderDown);

        Http::assertNothingSent();
    }
}
