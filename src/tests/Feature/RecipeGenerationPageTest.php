<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeGenerationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/recipes/create')->assertRedirect('/login');
    }

    public function test_page_shows_own_pantry_and_members_with_isolation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $potato = Ingredient::factory()->create(['name' => 'Картопля']);
        PantryItem::factory()->create([
            'user_id' => $user->id,
            'ingredient_id' => $potato->id,
        ]);

        FamilyMember::factory()->create(['user_id' => $user->id, 'name' => 'Мама']);
        FamilyMember::factory()->create(['user_id' => $other->id, 'name' => 'Стороння Особа']);

        $response = $this->actingAs($user)->get('/recipes/create');

        $response->assertOk();
        $response->assertSee('Картопля');
        $response->assertSee('Мама');
        $response->assertDontSee('Стороння Особа');
    }

    public function test_family_member_checkboxes_are_checked_by_default(): void
    {
        $user = User::factory()->create();
        $member = FamilyMember::factory()->create(['user_id' => $user->id, 'name' => 'Тато']);

        PantryItem::factory()->create([
            'user_id' => $user->id,
            'ingredient_id' => Ingredient::factory()->create(['name' => 'Морква'])->id,
        ]);

        $response = $this->actingAs($user)->get('/recipes/create');

        $response->assertOk();
        $response->assertSee('name="members[]" value="'.$member->id.'" checked', false);
    }

    public function test_empty_pantry_shows_notice_and_disables_button(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/recipes/create');

        $response->assertOk();
        $response->assertSee('Комора порожня.');
        $response->assertSee(route('pantry.create'), false);
        $response->assertSee('cursor-not-allowed', false); // disabled generate button
    }

    public function test_zero_family_members_renders_with_self_hint_and_active_button(): void
    {
        $user = User::factory()->create();
        PantryItem::factory()->create([
            'user_id' => $user->id,
            'ingredient_id' => Ingredient::factory()->create(['name' => 'Цибуля'])->id,
        ]);

        $response = $this->actingAs($user)->get('/recipes/create');

        $response->assertOk();
        $response->assertSee('без додаткових обмежень');
        $response->assertDontSee('cursor-not-allowed', false); // button is active
    }
}
