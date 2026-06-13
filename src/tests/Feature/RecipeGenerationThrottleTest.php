<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecipeGenerationThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function givePantry(User $user): void
    {
        PantryItem::factory()->create([
            'user_id' => $user->id,
            'ingredient_id' => Ingredient::factory()->create(['name' => 'Картопля'])->id,
        ]);
    }

    public function test_eleventh_generation_in_an_hour_is_throttled(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->givePantry($user);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)
                ->postJson('/api/recipes/generate', ['members' => []])
                ->assertOk();
        }

        $this->actingAs($user)
            ->postJson('/api/recipes/generate', ['members' => []])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Ви досягли ліміту — до 10 рецептів на годину. Спробуйте трохи пізніше.');
    }

    public function test_throttle_is_per_user(): void
    {
        Queue::fake();

        $userA = User::factory()->create();
        $this->givePantry($userA);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($userA)->postJson('/api/recipes/generate', ['members' => []])->assertOk();
        }

        // userA is now exhausted...
        $this->actingAs($userA)
            ->postJson('/api/recipes/generate', ['members' => []])
            ->assertStatus(429);

        // ...but userB has their own counter.
        $userB = User::factory()->create();
        $this->givePantry($userB);

        $this->actingAs($userB)
            ->postJson('/api/recipes/generate', ['members' => []])
            ->assertOk();
    }
}
