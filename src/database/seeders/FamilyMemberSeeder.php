<?php

namespace Database\Seeders;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class FamilyMemberSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        $members = [
            [
                'name' => 'Тато',
                'favorite_products' => 'Борщ, котлети, картопля',
                'disliked_products' => 'Гриби',
                'allergies_and_diets' => null,
            ],
            [
                'name' => 'Мама',
                'favorite_products' => 'Салати, риба, овочі',
                'disliked_products' => 'Свинина',
                'allergies_and_diets' => 'Без лактози',
            ],
            [
                'name' => 'Бабуся',
                'favorite_products' => 'Вареники, голубці, каші',
                'disliked_products' => 'Гострі страви',
                'allergies_and_diets' => 'Діабет 2 типу — без цукру',
            ],
            [
                'name' => 'Син',
                'favorite_products' => 'Паста, піца, курятина',
                'disliked_products' => 'Цибуля, варена морква',
                'allergies_and_diets' => 'Алергія на горіхи',
            ],
            [
                'name' => 'Донька',
                'favorite_products' => 'Фрукти, йогурти, млинці',
                'disliked_products' => 'Печінка',
                'allergies_and_diets' => 'Вегетаріанство',
            ],
        ];

        foreach ($members as $member) {
            FamilyMember::create([...$member, 'user_id' => $user->id]);
        }
    }
}
