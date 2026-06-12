<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'category' => fake()->randomElement(['Овочі', 'Фрукти', 'М\'ясо та птиця', 'Молочні продукти', 'Крупи та макарони']),
            'is_custom' => false,
            'created_by_user_id' => null,
        ];
    }

    /** A user-created custom ingredient. */
    public function custom(?User $user = null): static
    {
        return $this->state(fn () => [
            'is_custom' => true,
            'created_by_user_id' => $user?->id ?? User::factory(),
        ]);
    }
}
