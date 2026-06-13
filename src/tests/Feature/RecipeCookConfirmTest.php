<?php

namespace Tests\Feature;

use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeCookConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_confirmation_stub(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();

        $this->actingAs($user)->get(route('recipes.cook.confirm', $recipe))
            ->assertOk()
            ->assertSee('Підтвердження списання — незабаром.')
            ->assertSee(route('recipes.show', $recipe), false); // «Назад до рецепту»
    }

    public function test_confirmation_does_not_deduct_pantry_or_change_status(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();
        PantryItem::factory()->count(3)->for($user)->create();

        $this->actingAs($user)->get(route('recipes.cook.confirm', $recipe))
            ->assertOk();

        $this->assertSame(3, PantryItem::where('user_id', $user->id)->count());
        $this->assertSame('generated', $recipe->fresh()->status);
        $this->assertNull($recipe->fresh()->cooked_at);
    }

    public function test_non_owner_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::factory()->for($owner)->completed()->create();

        $this->actingAs($other)->get(route('recipes.cook.confirm', $recipe))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $recipe = Recipe::factory()->completed()->create();

        $this->get(route('recipes.cook.confirm', $recipe))
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
