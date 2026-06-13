<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_recipe_renders_full_card(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();

        $this->actingAs($user)->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Картопляне пюре')          // name
            ->assertSee('Просте й ситне пюре.')      // description
            ->assertSee('Картопля')                  // ingredient name
            ->assertSee('є в коморі')                // in_pantry true flag
            ->assertSee('треба купити')              // in_pantry false flag
            ->assertSee('Зварити картоплю')          // a step
            ->assertSee('250')                       // a kbju value
            ->assertSee('Порцій: 2');
    }

    public function test_completed_card_shows_both_action_buttons(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create();

        $this->actingAs($user)->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Приготовано')
            ->assertSee('В обране');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $recipe = Recipe::factory()->completed()->create();

        $this->get(route('recipes.show', $recipe))
            ->assertRedirect(route('login'));
    }

    public function test_non_owner_cannot_view_recipe(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::factory()->for($owner)->completed()->create();

        $this->actingAs($other)->get(route('recipes.show', $recipe))
            ->assertForbidden();
    }

    public function test_pending_recipe_shows_not_ready_state_instead_of_card(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->create(); // default: pending

        $this->actingAs($user)->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Рецепт ще не готовий.')
            ->assertDontSee('Інгредієнти');
    }

    public function test_failed_recipe_shows_error_and_retry_button(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->create([
            'generation_status' => GenerationStatus::Failed,
            'generation_error' => 'Сервіс генерації тимчасово недоступний. Спробуйте ще раз за хвилину.',
        ]);

        $this->actingAs($user)->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Не вдалося згенерувати рецепт.')                  // failed-state heading
            ->assertSee('Сервіс генерації тимчасово недоступний. Спробуйте ще раз за хвилину.') // friendly error
            ->assertSee('Спробувати ще раз')                               // retry button
            ->assertSee(route('recipes.create'), false)                    // retry links to a fresh generation
            ->assertDontSee('Інгредієнти');                                // not the card
    }

    public function test_owner_can_toggle_favorite(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create(['is_favorite' => false]);

        $this->actingAs($user)
            ->patch(route('recipes.favorite', $recipe))
            ->assertRedirect();
        $this->assertTrue($recipe->fresh()->is_favorite);

        $this->actingAs($user)
            ->patch(route('recipes.favorite', $recipe))
            ->assertRedirect();
        $this->assertFalse($recipe->fresh()->is_favorite);
    }

    public function test_non_owner_cannot_toggle_favorite(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recipe = Recipe::factory()->for($owner)->completed()->create(['is_favorite' => false]);

        $this->actingAs($other)
            ->patch(route('recipes.favorite', $recipe))
            ->assertForbidden();

        $this->assertFalse($recipe->fresh()->is_favorite);
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $recipe = Recipe::factory()->completed()->create();

        $this->patch(route('recipes.favorite', $recipe))
            ->assertRedirect(route('login'));
    }

    public function test_cooked_recipe_shows_badge_and_disabled_cook_button(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->for($user)->completed()->create([
            'status' => 'cooked',
            'cooked_at' => now(),
        ]);

        $this->actingAs($user)->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Вже приготовано')   // disabled cooked button copy
            ->assertSee('disabled', false);  // raw attribute present
    }
}
