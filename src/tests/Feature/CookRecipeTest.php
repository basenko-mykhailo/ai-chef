<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookRecipeTest extends TestCase
{
    use RefreshDatabase;

    private function pantryItem(User $user, string $name, float $qty): PantryItem
    {
        return PantryItem::factory()->create([
            'user_id' => $user->id,
            'ingredient_id' => Ingredient::factory()->create(['name' => $name])->id,
            'quantity' => $qty,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $recipe = Recipe::factory()->for(User::factory())->create();

        $this->post(route('recipes.cook.store', $recipe), ['items' => []])
            ->assertRedirect('/login');
    }

    public function test_cooking_deducts_quantities_and_marks_recipe_cooked(): void
    {
        $user = User::factory()->create();
        $potato = $this->pantryItem($user, 'Картопля', 2.0);
        $milk = $this->pantryItem($user, 'Молоко', 1.0);
        $recipe = Recipe::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('recipes.cook.store', $recipe), [
            'items' => [
                ['pantry_item_id' => $potato->id, 'quantity' => 0.5],
                ['pantry_item_id' => $milk->id, 'quantity' => 0.25],
            ],
        ]);

        $response->assertRedirect(route('recipes.show', $recipe));
        $response->assertSessionHas('recipe-cook-flash');

        $this->assertEqualsWithDelta(1.5, (float) $potato->fresh()->quantity, 0.001);
        $this->assertEqualsWithDelta(0.75, (float) $milk->fresh()->quantity, 0.001);

        $recipe->refresh();
        $this->assertSame('cooked', $recipe->status);
        $this->assertNotNull($recipe->cooked_at);
    }

    public function test_pantry_row_is_deleted_when_quantity_reaches_zero(): void
    {
        $user = User::factory()->create();
        $potato = $this->pantryItem($user, 'Картопля', 2.0);
        $recipe = Recipe::factory()->for($user)->create();

        $this->actingAs($user)->post(route('recipes.cook.store', $recipe), [
            'items' => [['pantry_item_id' => $potato->id, 'quantity' => 2.0]],
        ])->assertRedirect();

        $this->assertDatabaseMissing('pantry_items', ['id' => $potato->id]);
    }

    public function test_cannot_cook_another_users_recipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for(User::factory())->create();

        $this->actingAs($user)->post(route('recipes.cook.store', $recipe), ['items' => []])
            ->assertForbidden();
    }

    public function test_cannot_deduct_another_users_pantry_item(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherItem = $this->pantryItem($other, 'Картопля', 5.0);
        $recipe = Recipe::factory()->for($user)->create();

        $this->actingAs($user)->post(route('recipes.cook.store', $recipe), [
            'items' => [['pantry_item_id' => $otherItem->id, 'quantity' => 1.0]],
        ])->assertSessionHasErrors('items.0.pantry_item_id');

        $this->assertEqualsWithDelta(5.0, (float) $otherItem->fresh()->quantity, 0.001);
    }
}
