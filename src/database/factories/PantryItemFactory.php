<?php

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PantryItem>
 */
class PantryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity' => fake()->randomFloat(3, 1, 1000),
            'unit' => fake()->randomElement(Unit::cases())->value,
        ];
    }
}
