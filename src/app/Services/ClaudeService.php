<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Messages\Base64ImageSource;
use Anthropic\Messages\ImageBlockParam;
use Anthropic\Messages\TextBlock;
use Anthropic\Messages\TextBlockParam;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    /**
     * Достатньо для JSON-рецепта чи списку розпізнаних продуктів,
     * не впираючись у стелю Haiku 4.5 (64K output).
     */
    private const MAX_TOKENS = 8192;

    public function __construct(
        private readonly Client $client,
        private readonly string $defaultModel,
    ) {}

    /**
     * Текстова генерація. Retry на 429/529/5xx виконує сам SDK
     * (maxRetries у Client::requestOptions); тут — логування і проброс.
     *
     * @param  list<array{role: string, content: mixed}>  $messages
     */
    public function generateText(array $messages, ?string $system = null, ?string $model = null): string
    {
        $model ??= $this->defaultModel;

        try {
            $response = $this->client->messages->create(
                maxTokens: self::MAX_TOKENS,
                messages: $messages,
                model: $model,
                system: $system,
            );
        } catch (AnthropicException $e) {
            Log::error('Claude API call failed', [
                'model' => $model,
                'exception' => $e::class,
                'status' => $e instanceof APIStatusException ? $e->status : null,
                'error_type' => $e instanceof APIStatusException ? $e->type?->value : null,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $text = '';

        foreach ($response->content as $block) {
            if ($block instanceof TextBlock) {
                $text .= $block->text;
            }
        }

        return $text;
    }

    /**
     * Мультимодальна генерація: блоки зображень (base64) + текстовий промпт
     * одним user-повідомленням.
     *
     * @param  list<array{data: string, media_type: string}>  $images  base64-дані + jpeg/png/webp
     */
    public function generateFromImage(array $images, string $prompt, ?string $model = null): string
    {
        $content = array_map(
            fn (array $image) => ImageBlockParam::with(
                source: Base64ImageSource::with(
                    data: $image['data'],
                    mediaType: $image['media_type'],
                ),
            ),
            $images,
        );

        $content[] = TextBlockParam::with(text: $prompt);

        return $this->generateText(
            [['role' => 'user', 'content' => $content]],
            model: $model,
        );
    }
}
