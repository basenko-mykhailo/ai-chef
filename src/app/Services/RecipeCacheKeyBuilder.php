<?php

namespace App\Services;

/**
 * Будує детермінований cache-ключ для рецепту (тікет 3.9) із снапшотів, що вже
 * лежать на рядку рецепту: комора + обмеження обраних членів сім'ї. Однакова
 * комбінація комори й обмежень → однаковий ключ → переюз попередньої відповіді
 * Claude замість повторного (платного) виклику.
 *
 * Нормалізація прибирає випадкову різницю:
 *  - порядок продуктів / членів не впливає на ключ (сортуємо перед хешуванням);
 *  - ім'я члена сім'ї НЕ входить у ключ — на рецепт впливають лише обмеження,
 *    тож двоє членів з однаковими обмеженнями дедуплікуються в один ключ.
 */
class RecipeCacheKeyBuilder
{
    /**
     * @param  list<array{name?: string, quantity?: float|int|string, unit?: string}>  $pantrySnapshot
     * @param  list<array{favorite_products?: ?string, disliked_products?: ?string, allergies_and_diets?: ?string}>  $memberSnapshots
     */
    public function build(array $pantrySnapshot, array $memberSnapshots): string
    {
        $pantry = array_map(fn (array $item): array => [
            'name' => trim((string) ($item['name'] ?? '')),
            'quantity' => (float) ($item['quantity'] ?? 0),
            'unit' => trim((string) ($item['unit'] ?? '')),
        ], array_values($pantrySnapshot));

        usort($pantry, fn (array $a, array $b): int => [$a['name'], $a['unit'], $a['quantity']]
            <=> [$b['name'], $b['unit'], $b['quantity']]);

        $members = array_map(fn (array $member): array => [
            'favorite_products' => trim((string) ($member['favorite_products'] ?? '')),
            'disliked_products' => trim((string) ($member['disliked_products'] ?? '')),
            'allergies_and_diets' => trim((string) ($member['allergies_and_diets'] ?? '')),
        ], array_values($memberSnapshots));

        usort($members, fn (array $a, array $b): int => $a <=> $b);

        $payload = json_encode(
            ['pantry' => $pantry, 'members' => $members],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );

        return hash('sha256', $payload);
    }
}
