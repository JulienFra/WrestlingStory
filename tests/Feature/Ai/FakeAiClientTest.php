<?php

namespace Tests\Feature\Ai;

use App\Ai\AiErrorKind;
use App\Ai\AiException;
use App\Ai\AiProvider;
use App\Ai\FakeAiClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FakeAiClientTest extends TestCase
{
    public function test_fake_answers_a_deterministic_promo_without_network()
    {
        Http::fake();
        $client = new FakeAiClient(AiProvider::Fake);

        $first = $client->complete('Tu écris des promos.', 'Un bûcheron géant qui parle aux arbres', 300);
        $second = $client->complete('Tu écris des promos.', 'Un bûcheron géant qui parle aux arbres', 300);

        $this->assertNotSame('', trim($first->text));
        $this->assertStringContainsString('Un bûcheron géant qui parle aux arbres', $first->text);
        $this->assertSame($first->text, $second->text);
        $this->assertSame('fake', $first->model);
        $this->assertGreaterThan(0, $first->inputTokens);
        $this->assertGreaterThan(0, $first->outputTokens);
        $this->assertSame(0.0, $first->cost);
        Http::assertNothingSent();
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
    public function test_each_failing_fake_throws_its_error(AiProvider $provider, AiErrorKind $kind)
    {
        try {
            (new FakeAiClient($provider))->complete('Système', 'Prompt', 300);
            $this->fail('An AiException was expected.');
        } catch (AiException $exception) {
            $this->assertSame($kind, $exception->kind);
            $this->assertSame($kind->message(), $exception->getMessage());
        }
    }
}
