<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\User;

/**
 * Будує system + user промпти для генерації рецепту (тікет 3.4).
 *
 * Комора передається plain-масивом, бо PantryItem (Епік 1) ще не існує —
 * RecipeGenerationJob (3.8) змапить моделі в цей формат.
 */
class RecipePromptBuilder
{
    /**
     * @param  list<FamilyMember>  $members  обрані члени сім'ї (може бути порожнім — готуємо без обмежень)
     * @param  list<array{name: string, quantity: float|int|string, unit: string}>  $pantry
     * @return array{system: string, user: string}
     */
    public function build(User $user, array $members, array $pantry): array
    {
        return [
            'system' => $this->systemPrompt(),
            'user' => $this->userPrompt($members, $pantry),
        ];
    }

    private function systemPrompt(): string
    {
        $units = implode(', ', RecipeSchema::ALLOWED_UNITS);
        $structure = RecipeSchema::STRUCTURE;

        return <<<PROMPT
            Ти — досвідчений кухар-помічник. Твоє завдання — згенерувати один рецепт страви з продуктів, наявних у коморі користувача, з урахуванням обмежень членів сім'ї.

            Правила безпеки (найважливіше):
            - Алергії інтерпретуй ШИРОКО. Наприклад, "горіхи" означає виключити всі деревні горіхи (фундук, мигдаль, кешью, волоський горіх тощо) ТА арахіс. "Молочка" — все молочне, включно з маслом, сиром, вершками.
            - Якщо є хоч найменший сумнів, чи безпечний продукт для когось із зазначених людей — НЕ використовуй його.
            - Обмеження всіх зазначених людей діють ОДНОЧАСНО (логічне І): рецепт має задовольняти кожну алергію та дієту кожної людини зі списку.

            Правила рецепту:
            - Використовуй переважно продукти з комори, не перевищуючи наявні кількості.
            - Кількість кожного інгредієнта вказуй у ТІЙ САМІЙ одиниці виміру, в якій він поданий у коморі. Дозволені одиниці: {$units}.
            - Дозволено додати щонайбільше 1-2 базові інгредієнти, яких немає в коморі (сіль, олія, спеції) — познач їх "in_pantry": false. Усі інгредієнти з комори познач "in_pantry": true.
            - КБЖУ розраховуй НА ОДНУ ПОРЦІЮ.
            - Уся відповідь — українською мовою.

            Формат відповіді:
            - Поверни ЛИШЕ валідний JSON без markdown-обгорток (без ```), без пояснень до чи після.
            - Структура JSON (типи описані в значеннях, повертай реальні значення цих типів):
            {$structure}
            PROMPT;
    }

    /**
     * @param  list<FamilyMember>  $members
     * @param  list<array{name: string, quantity: float|int|string, unit: string}>  $pantry
     */
    private function userPrompt(array $members, array $pantry): string
    {
        $sections = [$this->pantrySection($pantry)];

        if ($members !== []) {
            $sections[] = $this->membersSection($members);
        }

        $sections[] = 'Згенеруй один рецепт страви з цих продуктів.';

        return implode("\n\n", $sections);
    }

    /**
     * @param  list<array{name: string, quantity: float|int|string, unit: string}>  $pantry
     */
    private function pantrySection(array $pantry): string
    {
        if ($pantry === []) {
            return 'Комора порожня.';
        }

        $lines = array_map(
            fn (array $item) => sprintf('- %s — %s %s', $item['name'], $item['quantity'], $item['unit']),
            $pantry,
        );

        return "Комора (наявні продукти):\n".implode("\n", $lines);
    }

    /**
     * @param  list<FamilyMember>  $members
     */
    private function membersSection(array $members): string
    {
        $blocks = array_map(function (FamilyMember $member) {
            $lines = ["Людина: {$member->name}"];

            if (filled($member->allergies_and_diets)) {
                $lines[] = "- Алергії та дієти (суворо виключити): {$member->allergies_and_diets}";
            }

            if (filled($member->disliked_products)) {
                $lines[] = "- Неулюблені продукти (уникати): {$member->disliked_products}";
            }

            if (filled($member->favorite_products)) {
                $lines[] = "- Улюблені продукти (бажано врахувати): {$member->favorite_products}";
            }

            return implode("\n", $lines);
        }, $members);

        return "Готуємо для таких людей (обмеження КОЖНОЇ людини обов'язкові):\n\n".implode("\n\n", $blocks);
    }
}
