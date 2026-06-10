<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Відповідь Claude не пройшла парсинг або валідацію схеми рецепту (тікет 3.6).
 *
 * Кожна фабрика називає конкретну причину — повідомлення підуть у логи
 * обробки помилок генерації (3.11).
 */
final class InvalidRecipeResponseException extends RuntimeException
{
    public static function invalidJson(string $detail): self
    {
        return new self("Invalid recipe response: malformed JSON ({$detail})");
    }

    public static function missingKey(string $key, string $context = 'response'): self
    {
        return new self("Invalid recipe response: missing key \"{$key}\" in {$context}");
    }

    public static function emptyList(string $key): self
    {
        return new self("Invalid recipe response: \"{$key}\" must be a non-empty list");
    }

    public static function invalidValue(string $key, string $expected): self
    {
        return new self("Invalid recipe response: \"{$key}\" must be {$expected}");
    }
}
