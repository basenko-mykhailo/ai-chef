<?php

namespace Database\Seeders;

use App\Enums\GenerationStatus;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Two ready-made recipes for the test user (ticket 7.6) so the recipe card,
 * history and favourites screens are populated for the demo without needing a
 * live Claude call. Idempotent (firstOrCreate on user + name).
 */
class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        Recipe::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Картопляне пюре з куркою'],
            [
                'description' => 'Ситна домашня страва: ніжне пюре та соковите куряче філе.',
                'ingredients_json' => [
                    ['name' => 'Картопля', 'quantity' => 0.6, 'unit' => 'кг', 'in_pantry' => true],
                    ['name' => 'Куряче філе', 'quantity' => 300, 'unit' => 'г', 'in_pantry' => true],
                    ['name' => 'Молоко', 'quantity' => 0.1, 'unit' => 'л', 'in_pantry' => true],
                    ['name' => 'Сіль', 'quantity' => 1, 'unit' => 'ч.л.', 'in_pantry' => false],
                ],
                'steps_json' => [
                    'Відваріть картоплю до мʼякості та злийте воду.',
                    'Додайте тепле молоко й сіль, розімніть у пюре.',
                    'Обсмажте куряче філе до золотистої скоринки.',
                    'Подавайте пюре разом із куркою.',
                ],
                'kbju_json' => ['kcal' => 420, 'protein' => 32, 'fat' => 12, 'carbs' => 48],
                'servings' => 2,
                'pantry_snapshot_json' => [],
                'selected_family_members_json' => [],
                'status' => 'generated',
                'generation_status' => GenerationStatus::Completed,
                'is_favorite' => true,
            ],
        );

        Recipe::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Рис із цибулею та яйцем'],
            [
                'description' => 'Швидкий обід із того, що вже є в коморі.',
                'ingredients_json' => [
                    ['name' => 'Рис', 'quantity' => 200, 'unit' => 'г', 'in_pantry' => true],
                    ['name' => 'Цибуля', 'quantity' => 1, 'unit' => 'шт', 'in_pantry' => true],
                    ['name' => 'Яйця курячі', 'quantity' => 2, 'unit' => 'шт', 'in_pantry' => true],
                    ['name' => 'Олія', 'quantity' => 1, 'unit' => 'ст.л.', 'in_pantry' => false],
                ],
                'steps_json' => [
                    'Відваріть рис до готовності.',
                    'Підсмажте дрібно нарізану цибулю на олії.',
                    'Вбийте яйця та перемішайте до готовності.',
                    'Зʼєднайте з рисом і прогрійте разом.',
                ],
                'kbju_json' => ['kcal' => 380, 'protein' => 14, 'fat' => 11, 'carbs' => 58],
                'servings' => 2,
                'pantry_snapshot_json' => [],
                'selected_family_members_json' => [],
                'status' => 'cooked',
                'generation_status' => GenerationStatus::Completed,
                'is_favorite' => false,
                'cooked_at' => now()->subDay(),
            ],
        );
    }
}
