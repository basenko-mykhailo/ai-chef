<?php

namespace App\Services;

use App\Exceptions\InvalidRecipeResponseException;
use JsonException;

/**
 * Парсить і валідує сиру текстову відповідь Claude проти контракту
 * RecipeSchema (тікет 3.6).
 *
 * Чиста логіка: без ClaudeService, мережі та контейнера — retry-цикл
 * отримує генерацію ззовні замиканням (у 3.8 це буде обгортка навколо
 * ClaudeService::generateText; transport-retry на 429/5xx робить сам SDK).
 */
class RecipeResponseParser
{
    /**
     * @return array{name: string, description: string, ingredients: list<array{name: string, quantity: float, unit: string, in_pantry: bool}>, steps: list<string>, kbju: array{kcal: float, protein: float, fat: float, carbs: float}, servings: int}
     *
     * @throws InvalidRecipeResponseException
     */
    public function parse(string $raw): array
    {
        $decoded = $this->decode($this->stripMarkdownFence($raw));

        foreach (RecipeSchema::REQUIRED_KEYS as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw InvalidRecipeResponseException::missingKey($key);
            }
        }

        $this->assertNonEmptyString($decoded['name'], 'name');
        $this->assertNonEmptyString($decoded['description'], 'description');

        return [
            'name' => $decoded['name'],
            'description' => $decoded['description'],
            'ingredients' => $this->validateIngredients($decoded['ingredients']),
            'steps' => $this->validateSteps($decoded['steps']),
            'kbju' => $this->validateKbju($decoded['kbju']),
            'servings' => $this->validateServings($decoded['servings']),
        ];
    }

    /**
     * Викликає $generate і парсить результат; при невалідній відповіді
     * повторює до $maxAttempts разів, після вичерпання кидає останній виняток.
     *
     * @param  callable(int): string  $generate  отримує номер спроби (з 1), повертає сиру відповідь моделі
     * @return array{name: string, description: string, ingredients: list<array{name: string, quantity: float, unit: string, in_pantry: bool}>, steps: list<string>, kbju: array{kcal: float, protein: float, fat: float, carbs: float}, servings: int}
     *
     * @throws InvalidRecipeResponseException
     */
    public function parseWithRetry(callable $generate, int $maxAttempts = 2): array
    {
        $maxAttempts = max(1, $maxAttempts);
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->parse($generate($attempt));
            } catch (InvalidRecipeResponseException $e) {
                $lastException = $e;
            }
        }

        throw $lastException;
    }

    /**
     * Модель інструктовано повертати JSON без обгорток (3.4), але ```json …
     * ``` — типовий збій, який дешевше зрізати, ніж витрачати retry-спробу.
     */
    private function stripMarkdownFence(string $raw): string
    {
        $trimmed = trim($raw);

        if (preg_match('/^```[a-zA-Z]*\s*(.*?)\s*```$/su', $trimmed, $matches) === 1) {
            return $matches[1];
        }

        return $trimmed;
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw InvalidRecipeResponseException::invalidJson($e->getMessage());
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw InvalidRecipeResponseException::invalidJson('expected a JSON object');
        }

        return $decoded;
    }

    /** @return list<array{name: string, quantity: float, unit: string, in_pantry: bool}> */
    private function validateIngredients(mixed $ingredients): array
    {
        if (! is_array($ingredients) || ! array_is_list($ingredients) || $ingredients === []) {
            throw InvalidRecipeResponseException::emptyList('ingredients');
        }

        return array_map(function (mixed $item): array {
            if (! is_array($item)) {
                throw InvalidRecipeResponseException::invalidValue('ingredients', 'a list of objects');
            }

            foreach (RecipeSchema::INGREDIENT_KEYS as $key) {
                if (! array_key_exists($key, $item)) {
                    throw InvalidRecipeResponseException::missingKey($key, 'ingredients');
                }
            }

            $this->assertNonEmptyString($item['name'], 'ingredients.name');

            if (! is_numeric($item['quantity']) || (float) $item['quantity'] <= 0) {
                throw InvalidRecipeResponseException::invalidValue('ingredients.quantity', 'a positive number');
            }

            if (! in_array($item['unit'], RecipeSchema::ALLOWED_UNITS, true)) {
                throw InvalidRecipeResponseException::invalidValue('ingredients.unit', 'one of: '.implode(', ', RecipeSchema::ALLOWED_UNITS));
            }

            if (! is_bool($item['in_pantry'])) {
                throw InvalidRecipeResponseException::invalidValue('ingredients.in_pantry', 'a boolean');
            }

            return [
                'name' => $item['name'],
                'quantity' => (float) $item['quantity'],
                'unit' => $item['unit'],
                'in_pantry' => $item['in_pantry'],
            ];
        }, $ingredients);
    }

    /** @return list<string> */
    private function validateSteps(mixed $steps): array
    {
        if (! is_array($steps) || ! array_is_list($steps) || $steps === []) {
            throw InvalidRecipeResponseException::emptyList('steps');
        }

        foreach ($steps as $step) {
            if (! is_string($step) || trim($step) === '') {
                throw InvalidRecipeResponseException::invalidValue('steps', 'a list of non-empty strings');
            }
        }

        return $steps;
    }

    /** @return array{kcal: float, protein: float, fat: float, carbs: float} */
    private function validateKbju(mixed $kbju): array
    {
        if (! is_array($kbju)) {
            throw InvalidRecipeResponseException::invalidValue('kbju', 'an object');
        }

        $validated = [];

        foreach (RecipeSchema::KBJU_KEYS as $key) {
            if (! array_key_exists($key, $kbju)) {
                throw InvalidRecipeResponseException::missingKey($key, 'kbju');
            }

            if (! is_numeric($kbju[$key]) || (float) $kbju[$key] < 0) {
                throw InvalidRecipeResponseException::invalidValue("kbju.{$key}", 'a non-negative number');
            }

            $validated[$key] = (float) $kbju[$key];
        }

        return $validated;
    }

    private function validateServings(mixed $servings): int
    {
        if (! is_numeric($servings) || (int) $servings < 1) {
            throw InvalidRecipeResponseException::invalidValue('servings', 'a positive number');
        }

        return (int) $servings;
    }

    private function assertNonEmptyString(mixed $value, string $key): void
    {
        if (! is_string($value) || trim($value) === '') {
            throw InvalidRecipeResponseException::invalidValue($key, 'a non-empty string');
        }
    }
}
