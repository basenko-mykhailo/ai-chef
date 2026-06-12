<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_pantry(): void
    {
        $this->get(route('pantry.index'))->assertRedirect('/login');
    }

    public function test_user_sees_only_own_pantry_items(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = Ingredient::factory()->create(['name' => 'Картопля']);
        PantryItem::factory()->create(['user_id' => $user->id, 'ingredient_id' => $mine->id]);
        PantryItem::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->get(route('pantry.index'))
            ->assertOk()
            ->assertSee('Картопля');
    }

    public function test_store_with_existing_ingredient(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create(['name' => 'Молоко']);

        $this->actingAs($user)->post(route('pantry.store'), [
            'ingredient_name' => 'Молоко',
            'ingredient_id' => $ingredient->id,
            'quantity' => 1.5,
            'unit' => 'l',
        ])->assertRedirect(route('pantry.index'));

        $this->assertDatabaseHas('pantry_items', [
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'unit' => 'l',
        ]);
    }

    public function test_store_creates_custom_ingredient_on_the_fly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('pantry.store'), [
            'ingredient_name' => 'Бабусине вариво',
            'quantity' => 2,
            'unit' => 'pcs',
        ])->assertRedirect(route('pantry.index'));

        $this->assertDatabaseHas('ingredients', [
            'name' => 'Бабусине вариво',
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);
        $ingredient = Ingredient::where('name', 'Бабусине вариво')->firstOrFail();
        $this->assertDatabaseHas('pantry_items', [
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
        ]);
    }

    public function test_quantity_must_be_positive(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('pantry.store'), [
            'ingredient_name' => 'Сіль',
            'quantity' => 0,
            'unit' => 'g',
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('pantry_items', 0);
    }

    public function test_unit_must_be_valid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('pantry.store'), [
            'ingredient_name' => 'Сіль',
            'quantity' => 1,
            'unit' => 'банка',
        ])->assertSessionHasErrors('unit');
    }

    public function test_user_can_update_own_item(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create(['name' => 'Рис']);
        $item = PantryItem::factory()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 100,
            'unit' => 'g',
        ]);

        $this->actingAs($user)->patch(route('pantry.update', $item), [
            'ingredient_name' => 'Рис',
            'ingredient_id' => $ingredient->id,
            'quantity' => 750,
            'unit' => 'g',
        ])->assertRedirect(route('pantry.index'));

        $this->assertDatabaseHas('pantry_items', [
            'id' => $item->id,
            'quantity' => 750,
        ]);
    }

    public function test_user_cannot_update_other_users_item(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = PantryItem::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->patch(route('pantry.update', $item), [
            'ingredient_name' => 'Хак',
            'quantity' => 1,
            'unit' => 'g',
        ])->assertForbidden();
    }

    public function test_user_can_delete_own_item(): void
    {
        $user = User::factory()->create();
        $item = PantryItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('pantry.destroy', $item))
            ->assertRedirect(route('pantry.index'));

        $this->assertDatabaseMissing('pantry_items', ['id' => $item->id]);
    }

    public function test_user_cannot_delete_other_users_item(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = PantryItem::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->delete(route('pantry.destroy', $item))->assertForbidden();
        $this->assertDatabaseHas('pantry_items', ['id' => $item->id]);
    }
}
