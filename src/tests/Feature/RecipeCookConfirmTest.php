<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeCookConfirmTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The `completed()` recipe factory ships «Картопля» 500 г (in_pantry:true)
     * and «Сіль» 1 ч.л. (in_pantry:false) — used across the matching cases below.
     */
    private function potatoPantryItem(User $user, float $quantity, string $unit): PantryItem
    {
        $potato = Ingredient::factory()->create(['name' => 'Картопля']);

        return PantryItem::factory()->for($user)->create([
            'ingredient_id' => $potato->id,
            'quantity' => $quantity,
            'unit' => $unit,
        ]);
    }

    public function test_owner_sees_matched_ingredient_with_prefilled_quantity(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();
        $this->potatoPantryItem($user, 800, 'g'); // label «г» matches the recipe unit

        $this->actingAs($user)->get(route('recipes.cook.confirm', $recipe))
            ->assertOk()
            ->assertSee('Картопля')
            ->assertSee('value="500"', false)                          // pre-filled from the recipe
            ->assertSee('у коморі: 800 г')                             // live pantry hint
            ->assertSee(route('recipes.cook.store', $recipe), false)   // editable form is present
            ->assertDontSee('Сіль');                                  // in_pantry:false staple excluded
    }

    public function test_unit_mismatch_excludes_ingredient(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();
        $this->potatoPantryItem($user, 5, 'kg'); // label «кг» ≠ recipe «г»

        $this->actingAs($user)->get(route('recipes.cook.confirm', $recipe))
            ->assertOk()
            ->assertSee('Нема чого списувати.')
            ->assertDontSee(route('recipes.cook.store', $recipe), false); // no submit form
    }

    public function test_no_matching_pantry_shows_empty_state_without_form(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();

        $this->actingAs($user)->get(route('recipes.cook.confirm', $recipe))
            ->assertOk()
            ->assertSee('Нема чого списувати.')
            ->assertDontSee(route('recipes.cook.store', $recipe), false);
    }

    public function test_viewing_confirmation_does_not_deduct_pantry_or_change_status(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();
        $this->potatoPantryItem($user, 800, 'g');

        $this->actingAs($user)->get(route('recipes.cook.confirm', $recipe))
            ->assertOk();

        $this->assertSame(1, PantryItem::where('user_id', $user->id)->count());
        $this->assertSame('generated', $recipe->fresh()->status);
        $this->assertNull($recipe->fresh()->cooked_at);
    }

    public function test_cook_post_deducts_pantry_and_marks_recipe_cooked(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();
        $pantryItem = $this->potatoPantryItem($user, 800, 'g');

        $this->actingAs($user)->post(route('recipes.cook.store', $recipe), [
            'items' => [
                $pantryItem->id => ['pantry_item_id' => $pantryItem->id, 'quantity' => 500],
            ],
        ])->assertRedirect(route('recipes.show', $recipe));

        $this->assertSame('300.000', $pantryItem->fresh()->quantity); // 800 − 500 (4.3)
        $this->assertSame('cooked', $recipe->fresh()->status);        // status flip (4.4)
        $this->assertNotNull($recipe->fresh()->cooked_at);
    }

    public function test_non_owner_is_forbidden_on_confirm_and_store(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::factory()->for($owner)->completed()->create();

        $this->actingAs($other)->get(route('recipes.cook.confirm', $recipe))
            ->assertForbidden();

        $this->actingAs($other)->post(route('recipes.cook.store', $recipe))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $recipe = Recipe::factory()->completed()->create();

        $this->get(route('recipes.cook.confirm', $recipe))
            ->assertRedirect(route('login'));

        $this->post(route('recipes.cook.store', $recipe))
            ->assertRedirect(route('login'));
    }

    public function test_completed_card_links_to_confirmation_and_drops_placeholder(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();

        $this->actingAs($user)->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee(route('recipes.cook.confirm', $recipe), false) // active link
            ->assertDontSee('Списання комори — незабаром');             // old disabled caption gone
    }

    public function test_cooked_recipe_keeps_disabled_button_without_confirm_link(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create([
            'status' => 'cooked',
            'cooked_at' => now(),
        ]);

        $this->actingAs($user)->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Вже приготовано')
            ->assertDontSee(route('recipes.cook.confirm', $recipe), false);
    }
}
