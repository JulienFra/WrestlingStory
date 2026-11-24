<?php

namespace Tests\Feature\Ai;

use App\Ai\AiJsonSchema;
use App\Ai\OpenRouterClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterClientJsonTest extends TestCase
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

        Http::fake(['openrouter.test/*' => Http::response([
            'model' => 'test/model-small',
            'choices' => [['message' => ['content' => '{"winner_id": 12}']]],
            'usage' => ['prompt_tokens' => 42, 'completion_tokens' => 17, 'cost' => 0.0001],
        ])]);
    }

    public function test_with_a_schema_the_request_asks_for_a_strict_json_answer()
    {
        $schema = [
            'type' => 'object',
            'properties' => ['winner_id' => ['type' => 'integer', 'enum' => [12, 7]]],
            'required' => ['winner_id'],
            'additionalProperties' => false,
        ];

        $completion = (new OpenRouterClient)->complete('Système', 'Un match', 300, new AiJsonSchema('main_event', $schema));

        $this->assertSame('{"winner_id": 12}', $completion->text);
        Http::assertSent(fn (Request $request) => $request['response_format'] === [
            'type' => 'json_schema',
            'json_schema' => ['name' => 'main_event', 'strict' => true, 'schema' => $schema],
        ]
            && $request['provider'] === ['zdr' => true, 'data_collection' => 'deny', 'require_parameters' => true]
            && $request['max_tokens'] === 300
            && $request['usage'] === ['include' => true]);
    }

    public function test_without_a_schema_the_request_is_a_plain_chat_completion()
    {
        (new OpenRouterClient)->complete('Système', 'Un catcheur masqué', 200);

        Http::assertSent(fn (Request $request) => ! isset($request['response_format'])
            && $request['provider'] === ['zdr' => true, 'data_collection' => 'deny']
            && array_keys($request->data()) === ['model', 'messages', 'max_tokens', 'provider', 'usage']);
    }
}
