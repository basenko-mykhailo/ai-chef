<?php

namespace Database\Factories;

use App\Enums\GenerationStatus;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Placeholders for the NOT NULL AI columns on a pending recipe.
            'name' => '',
            'description' => null,
            'ingredients_json' => [],
            'steps_json' => [],
            'kbju_json' => [],
            'pantry_snapshot_json' => [
                ['name' => 'Картопля', 'quantity' => 500.0, 'unit' => 'г'],
            ],
            'selected_family_members_json' => [],
            'servings' => null,
            'status' => 'generated',
            'generation_status' => GenerationStatus::Pending,
            'generation_error' => null,
            'is_favorite' => false,
        ];
    }

    /** A finished recipe with all AI-populated columns filled. */
    public function completed(): static
    {
        return $this->state(fn () => [
            'name' => 'Картопляне пюре',
            'description' => 'Просте й ситне пюре.',
            'ingredients_json' => [
                ['name' => 'Картопля', 'quantity' => 500.0, 'unit' => 'г', 'in_pantry' => true],
                ['name' => 'Сіль', 'quantity' => 1.0, 'unit' => 'ч.л.', 'in_pantry' => false],
            ],
            'steps_json' => ['Зварити картоплю', 'Розім\'яти й посолити'],
            'kbju_json' => ['kcal' => 250.0, 'protein' => 8.0, 'fat' => 5.0, 'carbs' => 40.0],
            'servings' => 2,
            'generation_status' => GenerationStatus::Completed,
        ]);
    }
}
