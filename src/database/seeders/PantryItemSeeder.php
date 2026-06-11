<?php

namespace Database\Seeders;

use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A small starter pantry for the test user so the pantry pages have data to show.
 * Runs after IngredientSeeder (needs catalog rows) and the test user.
 */
class PantryItemSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        $items = [
            ['Картопля', 2, Unit::Kilogram],
            ['Куряче філе', 800, Unit::Gram],
            ['Молоко', 1, Unit::Liter],
            ['Яйця курячі', 10, Unit::Piece],
            ['Рис', 500, Unit::Gram],
            ['Цибуля', 3, Unit::Piece],
        ];

        foreach ($items as [$name, $quantity, $unit]) {
            $ingredient = Ingredient::where('name', $name)->first();

            if (! $ingredient) {
                continue;
            }

            PantryItem::firstOrCreate(
                ['user_id' => $user->id, 'ingredient_id' => $ingredient->id],
                ['quantity' => $quantity, 'unit' => $unit->value],
            );
        }
    }
}
