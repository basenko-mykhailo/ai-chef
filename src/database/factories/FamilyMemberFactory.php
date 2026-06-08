<?php

namespace Database\Factories;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyMember>
 */
class FamilyMemberFactory extends Factory
{
    public function definition(): array
    {
        $names = ['Тато', 'Мама', 'Бабуся', 'Дідусь', 'Син', 'Донька', 'Брат', 'Сестра'];
        $favorites = ['Борщ, вареники, картопля', 'Курятина, рис, овочі', 'Паста, сир, томати', 'Каші, фрукти, йогурт'];
        $disliked = ['Гриби', 'Морепродукти', 'Гострі страви', 'Кориця'];
        $allergiesAndDiets = ['Алергія на горіхи', 'Без лактози', 'Вегетаріанство', 'Без глютену', null];

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($names),
            'favorite_products' => fake()->randomElement($favorites),
            'disliked_products' => fake()->randomElement($disliked),
            'allergies_and_diets' => fake()->randomElement($allergiesAndDiets),
        ];
    }
}
