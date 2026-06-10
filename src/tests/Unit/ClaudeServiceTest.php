<?php

namespace Tests\Unit;

use Anthropic\Client;
use Anthropic\Core\Exceptions\BadRequestException;
use App\Services\ClaudeService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class ClaudeServiceTest extends TestCase
{
    /**
     * PSR-18 стаб: записує запити, віддає заготовлену відповідь — без мережі.
     */
    private function fakeTransporter(ResponseInterface $response): ClientInterface
    {
        return new class($response) implements ClientInterface
        {
            /** @var list<RequestInterface> */
            public array $requests = [];

            public function __construct(private readonly ResponseInterface $response) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->requests[] = $request;

                return $this->response;
            }
        };
    }

    private function successResponse(string $text): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-haiku-4-5',
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
        ]));
    }

    private function serviceWith(ClientInterface $transporter, string $defaultModel = 'claude-haiku-4-5'): ClaudeService
    {
        $client = new Client(
            apiKey: 'test-key',
            requestOptions: ['transporter' => $transporter, 'maxRetries' => 0],
        );

        return new ClaudeService($client, $defaultModel);
    }

    public function test_generate_text_returns_text_and_sends_expected_payload(): void
    {
        $transporter = $this->fakeTransporter($this->successResponse('Привіт'));
        $service = $this->serviceWith($transporter);

        $result = $service->generateText(
            [['role' => 'user', 'content' => 'Скажи привіт']],
            system: 'Ти — кухар.',
        );

        $this->assertSame('Привіт', $result);

        $request = $transporter->requests[0];
        $body = json_decode((string) $request->getBody(), true);

        $this->assertSame('claude-haiku-4-5', $body['model']);
        $this->assertSame('Ти — кухар.', $body['system']);
        $this->assertSame(8192, $body['max_tokens']);
        $this->assertSame('Скажи привіт', $body['messages'][0]['content']);
        $this->assertSame('test-key', $request->getHeaderLine('x-api-key'));
    }

    public function test_generate_text_with_explicit_model_overrides_default(): void
    {
        $transporter = $this->fakeTransporter($this->successResponse('ok'));
        $service = $this->serviceWith($transporter);

        $service->generateText(
            [['role' => 'user', 'content' => 'hi']],
            model: 'claude-sonnet-4-6',
        );

        $body = json_decode((string) $transporter->requests[0]->getBody(), true);
        $this->assertSame('claude-sonnet-4-6', $body['model']);
    }

    public function test_generate_from_image_builds_image_and_text_blocks(): void
    {
        $transporter = $this->fakeTransporter($this->successResponse('сир, помідори'));
        $service = $this->serviceWith($transporter);

        $result = $service->generateFromImage(
            [
                ['data' => 'YWJj', 'media_type' => 'image/png'],
                ['data' => 'ZGVm', 'media_type' => 'image/jpeg'],
            ],
            'Перерахуй продукти на фото',
        );

        $this->assertSame('сир, помідори', $result);

        $body = json_decode((string) $transporter->requests[0]->getBody(), true);
        $content = $body['messages'][0]['content'];

        $this->assertCount(3, $content);
        $this->assertSame('image', $content[0]['type']);
        $this->assertSame('base64', $content[0]['source']['type']);
        $this->assertSame('YWJj', $content[0]['source']['data']);
        $this->assertSame('image/png', $content[0]['source']['media_type']);
        $this->assertSame('image/jpeg', $content[1]['source']['media_type']);
        $this->assertSame('text', $content[2]['type']);
        $this->assertSame('Перерахуй продукти на фото', $content[2]['text']);
    }

    public function test_api_error_is_logged_and_rethrown(): void
    {
        $transporter = $this->fakeTransporter(new Response(
            400,
            ['Content-Type' => 'application/json'],
            json_encode([
                'type' => 'error',
                'error' => ['type' => 'invalid_request_error', 'message' => 'bad request'],
            ]),
        ));
        $service = $this->serviceWith($transporter);

        Log::shouldReceive('error')->once()->withArgs(
            fn (string $message, array $context) => $message === 'Claude API call failed'
                && $context['model'] === 'claude-haiku-4-5'
                && ! str_contains(json_encode($context), 'test-key'),
        );

        $this->expectException(BadRequestException::class);

        $service->generateText([['role' => 'user', 'content' => 'hi']]);
    }

    public function test_container_resolves_singleton_from_config(): void
    {
        config([
            'services.anthropic.api_key' => 'config-test-key',
            'services.anthropic.default_model' => 'claude-haiku-4-5',
        ]);

        $first = $this->app->make(ClaudeService::class);
        $second = $this->app->make(ClaudeService::class);

        $this->assertInstanceOf(ClaudeService::class, $first);
        $this->assertSame($first, $second);
    }
}
