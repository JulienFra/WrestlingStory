<?php

namespace Tests\Feature\Ai;

use App\Ai\AiErrorKind;
use App\Ai\AiException;
use App\Ai\AiJsonSchema;
use App\Ai\AiProvider;
use App\Ai\FakeAiClient;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FakeAiClientJsonTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $schema
     */
    private function answer(array $schema): mixed
    {
        $completion = (new FakeAiClient(AiProvider::Fake))->complete('Système', 'Prompt', 300, new AiJsonSchema('test', $schema));

        return json_decode($completion->text, true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_answer_follows_the_schema_and_is_deterministic()
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'winner_id' => ['type' => 'integer', 'enum' => [12, 7]],
                'intensity' => ['type' => 'integer', 'minimum' => 5],
                'score' => ['type' => 'number'],
                'story' => ['type' => 'string', 'maxLength' => 8],
                'follow_up' => ['type' => ['null', 'string']],
                'mood' => ['type' => 'string', 'enum' => ['calme', 'tendu']],
                'title_change' => ['type' => 'boolean'],
                'effects' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['delta' => ['type' => 'integer', 'minimum' => -5]],
                ]],
            ],
        ];

        $answer = $this->answer($schema);

        $this->assertSame([
            'winner_id' => 12,
            'intensity' => 5,
            'score' => 0,
            'story' => 'Un récit',
            'follow_up' => 'Un récit inventé par le faux fournisseur, sans appel au modèle.',
            'mood' => 'calme',
            'title_change' => true,
            'effects' => [['delta' => -5]],
        ], $answer);
        $this->assertSame($answer, $this->answer($schema));
    }

    public function test_object_without_properties_is_still_an_object()
    {
        $completion = (new FakeAiClient(AiProvider::Fake))->complete('Système', 'Prompt', 300, new AiJsonSchema('test', ['type' => 'object']));

        $this->assertSame('{}', $completion->text);
    }

    public function test_without_schema_it_still_answers_the_promo()
    {
        $completion = (new FakeAiClient(AiProvider::Fake))->complete('Système', 'Un catcheur masqué', 300);

        $this->assertStringContainsString('« Un catcheur masqué »', $completion->text);
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
    public function test_each_failing_fake_throws_its_error_with_a_schema(AiProvider $provider, AiErrorKind $kind)
    {
        try {
            (new FakeAiClient($provider))->complete('Système', 'Prompt', 300, new AiJsonSchema('test', ['type' => 'object']));
            $this->fail('An AiException was expected.');
        } catch (AiException $exception) {
            $this->assertSame($kind, $exception->kind);
        }
    }
}
