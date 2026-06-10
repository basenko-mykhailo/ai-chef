<?php

namespace Tests\Unit;

use App\Exceptions\InvalidRecipeResponseException;
use App\Services\RecipeResponseParser;
use App\Services\RecipeSchema;
use PHPUnit\Framework\TestCase;

class RecipeResponseParserTest extends TestCase
{
    private RecipeResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new RecipeResponseParser;
    }

    /** Канонічна валідна відповідь — окремі кейси мутують її під свій сценарій. */
    private function validPayload(): array
    {
        return [
            'name' => 'Картопляне пюре з молоком',
            'description' => 'Ніжне пюре на молоці з дрібкою солі.',
            'ingredients' => [
                ['name' => 'Картопля', 'quantity' => 1, 'unit' => 'кг', 'in_pantry' => true],
                ['name' => 'Молоко', 'quantity' => 200, 'unit' => 'мл', 'in_pantry' => true],
                ['name' => 'Сіль', 'quantity' => 1, 'unit' => 'ч.л.', 'in_pantry' => false],
            ],
            'steps' => ['Відварити картоплю.', 'Розтовкти з гарячим молоком і посолити.'],
            'kbju' => ['kcal' => 210, 'protein' => 5.2, 'fat' => 3.1, 'carbs' => 40],
            'servings' => 4,
        ];
    }

    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function test_parse_returns_normalized_array_for_valid_json(): void
    {
        $recipe = $this->parser->parse($this->encode($this->validPayload()));

        foreach (RecipeSchema::REQUIRED_KEYS as $key) {
            $this->assertArrayHasKey($key, $recipe);
        }

        $this->assertSame('Картопляне пюре з молоком', $recipe['name']);
        // числа нормалізовано: quantity/kbju → float, servings → int
        $this->assertSame(1.0, $recipe['ingredients'][0]['quantity']);
        $this->assertSame(210.0, $recipe['kbju']['kcal']);
        $this->assertSame(4, $recipe['servings']);
        $this->assertFalse($recipe['ingredients'][2]['in_pantry']);
    }

    public function test_parse_accepts_json_wrapped_in_markdown_fence(): void
    {
        $json = $this->encode($this->validPayload());

        foreach (["```json\n{$json}\n```", "```\n{$json}\n```"] as $fenced) {
            $recipe = $this->parser->parse($fenced);

            $this->assertSame('Картопляне пюре з молоком', $recipe['name']);
        }
    }

    public function test_parse_drops_unknown_top_level_keys(): void
    {
        $payload = $this->validPayload() + ['comment' => 'зайвий ключ від моделі'];

        $recipe = $this->parser->parse($this->encode($payload));

        $this->assertArrayNotHasKey('comment', $recipe);
        $this->assertSame(RecipeSchema::REQUIRED_KEYS, array_keys($recipe));
    }

    public function test_parse_rejects_malformed_json(): void
    {
        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('malformed JSON');

        $this->parser->parse('{ "name": "Борщ", ');
    }

    public function test_parse_rejects_non_object_json(): void
    {
        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('expected a JSON object');

        $this->parser->parse('"просто рядок, не обʼєкт"');
    }

    public function test_parse_rejects_each_missing_top_level_key(): void
    {
        foreach (RecipeSchema::REQUIRED_KEYS as $key) {
            $payload = $this->validPayload();
            unset($payload[$key]);

            try {
                $this->parser->parse($this->encode($payload));
                $this->fail("Очікувався виняток для відсутнього ключа \"{$key}\"");
            } catch (InvalidRecipeResponseException $e) {
                $this->assertStringContainsString($key, $e->getMessage());
            }
        }
    }

    public function test_parse_rejects_empty_ingredients(): void
    {
        $payload = $this->validPayload();
        $payload['ingredients'] = [];

        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('"ingredients" must be a non-empty list');

        $this->parser->parse($this->encode($payload));
    }

    public function test_parse_rejects_ingredient_with_missing_key(): void
    {
        $payload = $this->validPayload();
        unset($payload['ingredients'][0]['unit']);

        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('missing key "unit" in ingredients');

        $this->parser->parse($this->encode($payload));
    }

    public function test_parse_rejects_unit_outside_allowed_enum(): void
    {
        $payload = $this->validPayload();
        $payload['ingredients'][0]['unit'] = 'фунт';

        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('ingredients.unit');

        $this->parser->parse($this->encode($payload));
    }

    public function test_parse_rejects_non_numeric_quantity(): void
    {
        $payload = $this->validPayload();
        $payload['ingredients'][0]['quantity'] = 'багато';

        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('ingredients.quantity');

        $this->parser->parse($this->encode($payload));
    }

    public function test_parse_rejects_non_boolean_in_pantry(): void
    {
        $payload = $this->validPayload();
        $payload['ingredients'][0]['in_pantry'] = 'так';

        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('ingredients.in_pantry');

        $this->parser->parse($this->encode($payload));
    }

    public function test_parse_rejects_empty_steps(): void
    {
        $payload = $this->validPayload();
        $payload['steps'] = [];

        $this->expectException(InvalidRecipeResponseException::class);
        $this->expectExceptionMessage('"steps" must be a non-empty list');

        $this->parser->parse($this->encode($payload));
    }

    public function test_parse_rejects_missing_or_non_numeric_kbju_key(): void
    {
        $payload = $this->validPayload();
        unset($payload['kbju']['fat']);

        try {
            $this->parser->parse($this->encode($payload));
            $this->fail('Очікувався виняток для відсутнього kbju.fat');
        } catch (InvalidRecipeResponseException $e) {
            $this->assertStringContainsString('"fat" in kbju', $e->getMessage());
        }

        $payload = $this->validPayload();
        $payload['kbju']['kcal'] = 'багато';

        try {
            $this->parser->parse($this->encode($payload));
            $this->fail('Очікувався виняток для нечислового kbju.kcal');
        } catch (InvalidRecipeResponseException $e) {
            $this->assertStringContainsString('kbju.kcal', $e->getMessage());
        }
    }

    public function test_parse_with_retry_returns_first_valid_attempt(): void
    {
        $responses = ['не json взагалі', $this->encode($this->validPayload())];
        $calls = 0;

        $recipe = $this->parser->parseWithRetry(function (int $attempt) use (&$calls, $responses): string {
            $calls++;

            return $responses[$attempt - 1];
        });

        $this->assertSame(2, $calls);
        $this->assertSame('Картопляне пюре з молоком', $recipe['name']);
    }

    public function test_parse_with_retry_throws_after_exhausting_attempts(): void
    {
        $calls = 0;

        try {
            $this->parser->parseWithRetry(function () use (&$calls): string {
                $calls++;

                return 'не json взагалі';
            }, maxAttempts: 3);
            $this->fail('Очікувався виняток після вичерпання всіх спроб');
        } catch (InvalidRecipeResponseException $e) {
            $this->assertSame(3, $calls);
            $this->assertStringContainsString('malformed JSON', $e->getMessage());
        }
    }
}
