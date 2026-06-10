<?php

namespace App\Services;

/**
 * Канонічна структура відповіді Claude для згенерованого рецепту (тікет 3.5).
 *
 * STRUCTURE вставляється в system-промпт (3.4); масиви ключів — контракт
 * для парсера/валідації відповіді (3.6).
 */
final class RecipeSchema
{
    /**
     * Закритий enum одиниць виміру комори (PRD §3.2). Рецепт зобов'язаний
     * використовувати ті самі одиниці, що подані в коморі.
     */
    public const ALLOWED_UNITS = ['г', 'кг', 'мл', 'л', 'шт', 'ст.л.', 'ч.л.', 'склянка'];

    /** Обов'язкові ключі верхнього рівня відповіді. */
    public const REQUIRED_KEYS = ['name', 'description', 'ingredients', 'steps', 'kbju', 'servings'];

    /** Обов'язкові ключі кожного елемента ingredients. */
    public const INGREDIENT_KEYS = ['name', 'quantity', 'unit', 'in_pantry'];

    /** Обов'язкові ключі kbju (на порцію). */
    public const KBJU_KEYS = ['kcal', 'protein', 'fat', 'carbs'];

    public const STRUCTURE = <<<'JSON'
        {
          "name": "string — назва страви",
          "description": "string — короткий опис страви (1-2 речення)",
          "ingredients": [
            {
              "name": "string — назва інгредієнта",
              "quantity": "number — кількість",
              "unit": "string — одна з: г, кг, мл, л, шт, ст.л., ч.л., склянка",
              "in_pantry": "boolean — true якщо інгредієнт є в коморі, false для доданих базових"
            }
          ],
          "steps": ["string — крок приготування, по одному на елемент"],
          "kbju": {
            "kcal": "number — ккал на порцію",
            "protein": "number — білки, г на порцію",
            "fat": "number — жири, г на порцію",
            "carbs": "number — вуглеводи, г на порцію"
          },
          "servings": "number — кількість порцій"
        }
        JSON;
}
